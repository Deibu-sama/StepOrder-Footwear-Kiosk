<?php
return ['firestore'=>['project_id'=>env('FIREBASE_PROJECT_ID'),'service_account_json'=>base_path(env('FIREBASE_SERVICE_ACCOUNT_JSON','storage/app/firebase/service-account.json'))]];