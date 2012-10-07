<?php
namespace Lollipop\Wrappers\Db {
    require (\Lollipop::path('/libs/RedBeanPHP3_3_2/rb.php'));
    
    class Redbean extends \R {
        
        public static function ready() {
            $settings = \Lollipop::config('Redbean');
            // get our dsn vars from database
            $dsn = $settings['type'];

            // if the port is not set, default to the standard mysql port
            $port = ($settings['port']) ? $settings['port'] : 3307;
            // PDO, localhost and shared environments don't play nice - http://www.wolfcms.org/forum/topic1296.html
            // so, we fool it :)
            if ($settings['host'] == 'localhost' && $settings['options']['shared_hosting']) {
                $dsn .= ':host=127.0.0.1';
                $dsn .= ';port=' . $port;
            } else {
                $dsn .= ':host=' . $settings['host'];
                if ($settings['port']) {
                    $dsn .= ';port=' . $port;
                }
            }

            $dsn .= ';dbname=' . $settings['database'];
            $user = $settings['username'];
            $pass = $settings['password'];

            // Craig's modification for encrypted connection
            if ($settings['options']['SSL_encryption']['on']) {
                $dsn = self::setupSSL($dsn, $user, $pass, $settings['options']['SSL_encryption']);
                self::setup($dsn, $user, $pass);
            } else {
                self::setup($dsn, $user, $pass);
            }
            if ($settings['use_utf8']) {
                self::exec('SET CHARACTER SET utf8');
            }

            // if Sitch is in Production mode, freeze redbean
            if ($settings['freeze'] || !\Lollipop::getInstance()->Debug) {
                self::freeze();
            }
            // gc
            unset($dsn, $settings, $user, $pass, $port);

            return true;
        }
        
        public static function setupSSL($dsn, $user, $pass, $ssl_settings) {

            $cert_info = array(
                PDO::MYSQL_ATTR_SSL_KEY => $ssl_settings['mysql_attr_ssl_key'],
                PDO::MYSQL_ATTR_SSL_CERT => $ssl_settings['mysql_attr_ssl_cert'],
                PDO::MYSQL_ATTR_SSL_CA => $ssl_settings['mysql_attr_ssl_ca'],
                1002 => 'SET NAMES utf8',
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            );

            $pdo = new PDO($dsn, $user, $pass, $cert_info);
            $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
            // gc
            unset($dsn, $user, $pass, $ssl_settings, $cert_info);
            return $pdo;
        }
    }
    
    // set up everything straight away, we're not really interested in binding it, 
    Redbean::ready();
}
