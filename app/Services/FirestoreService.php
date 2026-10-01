<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class FirestoreService
{
    private ?string $accessToken = null;
    private int $tokenExpiresAt = 0;

    public function list(string $collection): array
    {
        $url = $this->baseUrl().'/'.$this->encodePath($collection).'?pageSize=300';
        $response = $this->request('GET', $url);
        return array_map([$this, 'decodeDocument'], $response['documents'] ?? []);
    }

    public function find(string $collection, string $id): ?array
    {
        $url = $this->baseUrl().'/'.$this->encodePath($collection).'/'.$this->encodePath($id);
        try {
            return $this->decodeDocument($this->request('GET', $url));
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), '[404]')) return null;
            throw $e;
        }
    }

    public function create(string $collection, array $data, ?string $id = null): string
    {
        $id ??= Str::lower((string) Str::uuid());
        $url = $this->baseUrl().'/'.$this->encodePath($collection).'?documentId='.$this->encodePath($id);
        $result = $this->request('POST', $url, ['fields' => $this->encodeFields($data)]);
        return basename($result['name'] ?? $id);
    }

    public function update(string $collection, string $id, array $data): array
    {
        $url = $this->baseUrl().'/'.$this->encodePath($collection).'/'.$this->encodePath($id);
        $query = [];
        foreach (array_keys($data) as $field) $query[] = 'updateMask.fieldPaths='.$this->encodePath($field);
        $url .= '?'.implode('&', $query);
        return $this->decodeDocument($this->request('PATCH', $url, ['fields' => $this->encodeFields($data)]));
    }

    public function delete(string $collection, string $id): void
    {
        $url = $this->baseUrl().'/'.$this->encodePath($collection).'/'.$this->encodePath($id);
        $this->request('DELETE', $url);
    }

    public function findByField(string $collection, string $field, mixed $value): array
    {
        return array_values(array_filter($this->list($collection), fn (array $row) => ($row[$field] ?? null) == $value));
    }

    private function baseUrl(): string
    {
        $projectId = config('services.firestore.project_id');
        if (!$projectId) throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        return 'https://firestore.googleapis.com/v1/projects/'.rawurlencode($projectId).'/databases/(default)/documents';
    }

    private function request(string $method, string $url, ?array $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$this->getAccessToken(), 'Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $error) throw new RuntimeException('Firestore request failed: '.$error);
        $decoded = json_decode($raw, true);
        if ($status < 200 || $status >= 300) throw new RuntimeException('Firestore API error ['.$status.']: '.($decoded['error']['message'] ?? $raw));
        return $decoded ?? [];
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiresAt - 60) return $this->accessToken;
        $path = config('services.firestore.service_account_json');
        if (!$path || !is_file($path)) throw new RuntimeException('Firestore service account file not found: '.$path);
        $credentials = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg'=>'RS256','typ'=>'JWT'], JSON_THROW_ON_ERROR));
        $claim = $this->base64UrlEncode(json_encode(['iss'=>$credentials['client_email'],'scope'=>'https://www.googleapis.com/auth/datastore','aud'=>'https://oauth2.googleapis.com/token','iat'=>$now,'exp'=>$now+3600], JSON_THROW_ON_ERROR));
        $unsigned = $header.'.'.$claim;
        if (!openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) throw new RuntimeException('Unable to sign service-account JWT.');
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$unsigned.'.'.$this->base64UrlEncode($signature)]),CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],CURLOPT_TIMEOUT=>20]);
        $raw = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); $error = curl_error($ch); curl_close($ch);
        if ($raw === false || $error) throw new RuntimeException('Unable to obtain Google access token: '.$error);
        $token = json_decode($raw, true);
        if ($status < 200 || $status >= 300 || empty($token['access_token'])) throw new RuntimeException('Google token request failed ['.$status.']: '.($token['error_description'] ?? $raw));
        $this->accessToken = $token['access_token']; $this->tokenExpiresAt = $now + (int)($token['expires_in'] ?? 3600);
        return $this->accessToken;
    }

    private function encodeFields(array $data): array { $fields=[]; foreach($data as $k=>$v) $fields[$k]=$this->encodeValue($v); return $fields; }
    private function encodeValue(mixed $value): array {
        if ($value===null) return ['nullValue'=>null];
        if (is_bool($value)) return ['booleanValue'=>$value];
        if (is_int($value)) return ['integerValue'=>(string)$value];
        if (is_float($value)) return ['doubleValue'=>$value];
        if (is_string($value)) return ['stringValue'=>$value];
        if (is_array($value)) {
            $isList=array_keys($value)===range(0,count($value)-1);
            return $isList ? ['arrayValue'=>['values'=>array_map(fn($v)=>$this->encodeValue($v),$value)]] : ['mapValue'=>['fields'=>$this->encodeFields($value)]];
        }
        throw new RuntimeException('Unsupported Firestore value type: '.get_debug_type($value));
    }
    private function decodeDocument(array $document): array { $name=$document['name']??''; return ['id'=>$name?basename($name):null,...$this->decodeFields($document['fields']??[])]; }
    private function decodeFields(array $fields): array { $out=[]; foreach($fields as $k=>$v) $out[$k]=$this->decodeValue($v); return $out; }
    private function decodeValue(array $value): mixed {
        if(array_key_exists('nullValue',$value)) return null;
        if(array_key_exists('booleanValue',$value)) return $value['booleanValue'];
        if(array_key_exists('integerValue',$value)) return (int)$value['integerValue'];
        if(array_key_exists('doubleValue',$value)) return (float)$value['doubleValue'];
        if(array_key_exists('stringValue',$value)) return $value['stringValue'];
        if(array_key_exists('timestampValue',$value)) return $value['timestampValue'];
        if(array_key_exists('referenceValue',$value)) return $value['referenceValue'];
        if(array_key_exists('arrayValue',$value)) return array_map(fn($v)=>$this->decodeValue($v),$value['arrayValue']['values']??[]);
        if(array_key_exists('mapValue',$value)) return $this->decodeFields($value['mapValue']['fields']??[]);
        return null;
    }
    private function base64UrlEncode(string $value): string { return rtrim(strtr(base64_encode($value),'+/','-_'),'='); }
    private function encodePath(string $value): string { return rawurlencode($value); }
}