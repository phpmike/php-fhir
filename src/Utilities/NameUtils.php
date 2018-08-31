<?php namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Class NameUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class NameUtils
{
    /** @var array */
    public static $classNameSearch = [
        '.',
        '-',
    ];

    /** @var array */
    public static $classNameReplace = [
        '',
        '_',
    ];

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidVariableName($name)
    {
        return (bool)preg_match(PHPFHIR_VARIABLE_NAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidFunctionName($name)
    {
        return (bool)preg_match(PHPFHIR_FUNCTION_NAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidClassName($name)
    {
        return (bool)preg_match(PHPFHIR_CLASSNAME_REGEX, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isValidNSName($name)
    {
        return null === $name || '' === $name || (bool)preg_match(PHPFHIR_NAMESPACE_REGEX, $name);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getTypeClassName($name)
    {
        if (false !== ($pos = strpos($name, '-primitive'))) {
            $name = sprintf('%sPrimitive', substr($name, 0, $pos));
        } else {
            if (false !== ($pos = strpos($name, '-list'))) {
                $name = sprintf('%sList', substr($name, 0, $pos));
            }
        }

        if (preg_match('{^[a-z]}S', $name)) {
            $name = ucfirst($name);
        }

        return sprintf('FHIR%s', str_replace(self::$classNameSearch, self::$classNameReplace, $name));
    }

    /**
     * @param string $propName
     * @return string
     */
    public static function getPropertyMethodName($propName)
    {
        return ucfirst($propName);
    }

    /**
     * @param string $propName
     * @return string
     */
    public static function getPropertyVariableName($propName)
    {
        return sprintf('$%s', $propName);
    }
}
