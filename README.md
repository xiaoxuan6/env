laravel-admin login-captch
======
Installation

    1、composer require james.xue/env
    2、在菜单里添加路由  env

Configuration
 In the extensions section of the config/admin.php file, add some configuration that belongs to this extension.
 
     'extensions' => [
         'env' => [
             // set to false if you want to disable this extension
             'enable' => true,
         ]
     ]
 
 