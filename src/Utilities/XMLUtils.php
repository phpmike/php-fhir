<?php namespace PHPFHIR\Utilities;

use PHPFHIR\Enum\ElementTypeEnum;
use PHPFHIR\XSDMap;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class XMLUtils
 * @package PHPFHIR\Utilities
 */
abstract class XMLUtils
{
    /**
     * @param \SimpleXMLElement $extensionElement
     * @return null|string
     */
    public static function getBaseObjectName(\SimpleXMLElement $extensionElement)
    {
        if ('extension' !== $extensionElement->getName())
        {
            $xpath = $extensionElement->xpath('xs:complexContent/xs:extension');
            if (0 === count($xpath))
                $xpath = $extensionElement->xpath('xs:extension');

            if (0 === count($xpath))
                return null;

            $extensionElement = $xpath[0];
        }

        $attributes = $extensionElement->attributes();
        return (string)$attributes['base'];
    }

    /**
     * @param \SimpleXMLElement $restrictionElement
     * @return null|string
     */
    public static function getObjectRestrictionBaseName(\SimpleXMLElement $restrictionElement)
    {
        if ('restriction' !== $restrictionElement->getName())
        {
            $xpath = $restrictionElement->xpath('xs:complexContent/xs:restriction');
            if (0 === count($xpath))
                $xpath = $restrictionElement->xpath('xs:restriction');

            if (0 === count($xpath))
                return null;

            $restrictionElement = $xpath[0];
        }

        $attributes = $restrictionElement->attributes();
        return (string)$attributes['base'];
    }

    /**
     * @param \SimpleXMLElement $sxe
     * @return null|string
     */
    public static function getObjectNameFromElement(\SimpleXMLElement $sxe)
    {
        $attributes = $sxe->attributes();

        if ($name = $attributes['name'])
            return (string)$name;

        return null;
    }

    /**
     * @param \SimpleXMLElement $parent
     * @return null|\SimpleXMLElement
     */
    public static function getAnnotationElement(\SimpleXMLElement $parent)
    {
        $annotation = $parent->xpath('xs:annotation');
        if (1 === count($annotation))
            return $annotation[0];

        return null;
    }

    /**
     * @param \SimpleXMLElement $annotation
     * @return null|string|array
     */
    public static function getDocumentation(\SimpleXMLElement $annotation)
    {
        if ('annotation' !== $annotation->getName())
            $annotation = self::getAnnotationElement($annotation);

        if (null === $annotation)
            return null;

        $documentation = $annotation->xpath('xs:documentation');

        if (0 === count($documentation))
            return null;

        $return = array();
        foreach($documentation as $element)
        {
            $return[] = (string)$element;
        }
        return $return;
    }

    /**
     * @param string $xsdPath
     * @param string $outputNS
     * @return XSDMap
     */
    public static function buildXSDMap($xsdPath, $outputNS)
    {
        $xsdMap = new XSDMap();

        if (!file_exists($xsdPath.'fhir-base.xsd'))
            throw new \RuntimeException('Unable to locate "fhir-base.xsd"');

        // First get class references in fhir-base.xsd
        self::getClassesFromXSD(new \SplFileInfo($xsdPath . 'fhir-base.xsd'), $xsdMap, $outputNS);

        // Then scoop up the rest
        // TODO: Validate that, yes, certain files can be ignored.
        $finder = new Finder();
        $finder->files()
            ->in($xsdPath)
            ->ignoreDotFiles(true)
            ->name('*.xsd')
            ->notName('fhir-*.xsd')
            ->notName('xml.xsd');

        foreach($finder as $file)
        {
            /** @var SplFileInfo $file */
            self::getClassesFromXSD($file, $xsdMap, $outputNS);
        }

        return $xsdMap;
    }

    /**
     * @param \SplFileInfo $file
     * @param XSDMap $xsdMap
     * @param string $outputNS
     */
    public static function getClassesFromXSD(\SplFileInfo $file, XSDMap $xsdMap, $outputNS)
    {
        $sxe = SimpleXMLUtils::constructWithFileInfo($file);
        foreach($sxe->children('xs', true) as $child)
        {
            /** @var \SimpleXMLElement $child */
            $attributes = $child->attributes();
            $name = (string)$attributes['name'];

            if ('' === $name)
                continue;

//            // Primitive types represented as internal PHP types for the moment
//            // TODO: Implement primitive type classes with support for XML facets
//            if (false !== strpos($name, '-primitive'))
//                continue;

            switch(strtolower($child->getName()))
            {
                case ElementTypeEnum::COMPLEX_TYPE:
                    $type = ClassTypeUtils::getComplexClassType($child);
                    $rootNS = NSUtils::generateRootNamespace(
                        $outputNS,
                        NSUtils::getComplexTypeNamespace($name, $type)
                    );
                    $className = NameUtils::getComplexTypeClassName($name);
                    break;

                case ElementTypeEnum::SIMPLE_TYPE:
                    $type = ClassTypeUtils::getSimpleClassType($name);
                    $rootNS = NSUtils::generateRootNamespace(
                        $outputNS,
                        NSUtils::getSimpleTypeNamespace($type)
                    );
                    $className = NameUtils::getSimpleTypeClassName($name);
                    break;


                default: continue 2;
            }

            $nsSegments = explode('\\', $rootNS);
            if (0 === count($nsSegments))
                $pseudonym = sprintf('%sBase', $className);
            else
                $pseudonym = sprintf('%s%s', $className, end($nsSegments));

            $xsdMap[$name] = array(
                'sxe' => $child,
                'rootNS' => $rootNS,
                'className' => $className,
                'pseudonym' => $pseudonym,
            );
        }
    }
}