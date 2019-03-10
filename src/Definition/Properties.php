<?php

namespace DCarbone\PHPFHIR\Definition;

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Config\VersionConfig;

/**
 * Class Properties
 * @package DCarbone\PHPFHIR\Definition\Type
 */
class Properties implements \Countable
{
    /** @var \DCarbone\PHPFHIR\Definition\Property[] */
    private $properties = [];

    /** @var bool */
    private $sorted = false;

    /** @var */
    private $config;

    /** @var \DCarbone\PHPFHIR\Definition\Type */
    private $type;

    /**
     * Properties constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     */
    public function __construct(VersionConfig $config, Type $type)
    {
        $this->config = $config;
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return ['properties' => $this->properties];
    }

    /**
     * @return \DCarbone\PHPFHIR\Config\VersionConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Property $property
     * @return $this
     */
    public function addProperty(Property &$property)
    {
        $pname = $property->getName();
        foreach ($this->properties as $current) {
            if ($property === $current) {
                return $this;
            }
            if ($pname === $current->getName()) {
                $this->config->getLogger()->notice(sprintf(
                    'Type %s already has property %s, probably some duplicate definition nonsense. Keeping original.',
                    $this->getType()->getFHIRName(),
                    $property->getName()
                ));
                $property = $current;
                return $this;
            }
        }
        $this->properties[] = $property;
        $this->sorted = false;
        return $this;
    }

    /**
     * @param string $name
     * @return \DCarbone\PHPFHIR\Definition\Property|null
     */
    public function getProperty($name)
    {
        foreach ($this->properties as $property) {
            if ($property->getName() === $name) {
                return $property;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return null !== $this->getProperty($name);
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Property[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->properties);
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Property[]
     */
    public function getSortedIterator()
    {
        if (!$this->sorted) {
            usort(
                $this->properties,
                function ($a, $b) {
                    /** @var \DCarbone\PHPFHIR\Definition\Property $a */
                    /** @var \DCarbone\PHPFHIR\Definition\Property $b */
                    return strnatcmp($a->getName(), $b->getName());
                }
            );
            $this->sorted = true;
        }
        return $this->getIterator();
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->properties);
    }
}