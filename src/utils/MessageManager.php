<?php

namespace famima65536\EconomyCShop\utils;

use pocketmine\lang\Language;
use pocketmine\lang\Translatable;

class MessageManager{

    /**
     * @phpstan-param array<string, string> $messages
     */
    public function __construct(
        private array $messages, 
        private bool $clientSideTranslation
    ){
    }

    /**
     * @phpstan-param array<int, string> $params
     */
    public function get(string $key, array $params = []) : Translatable|string{
        $base = $this->messages[$key] ?? "";
        if($this->clientSideTranslation){
            return new Translatable($base, $params);
        }else{
            return self::replace($base, $params);
        }
    }

    /**
     * @phpstan-param array<int, string> $params
     */
    private static function replace(string $base, array $params) : string{
        foreach($params as $k => $v){
            $base = str_replace("%${k}", $v, $base);
        }
        return $base;
    }
}