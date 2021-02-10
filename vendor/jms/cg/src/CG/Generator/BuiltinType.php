<?php

namespace CG\Generator;

class BuiltinType
{
    private static $builtinTypes = array('self', 'array', 'callable', 'bool', 'float', 'int', 'string');
    
    private function __construct(){
        // Static class
    }

    public static function isBuiltin($type)
    {
        return in_array($type, static::$builtinTypes);
    }
}
    
    
 
