<?php
/**
 * This file is part of the Hal library
 *
 * (c) Ben Longden <ben@nocarrier.co.uk
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Nocarrier
 */

/**
 * The Hal document class
 *
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
class FikenHal
{
    /**
     * The uri represented by this representation.
     *
     * @var string
     */
    protected $uri;

    /**
     * The data for this resource. An associative array of key value pairs.
     *
     * array(
     *     'price' => 30.00,
     *     'colour' => 'blue'
     * )
     *
     * @var array
     */
    protected $data;

    /**
     * An array of embedded Hal objects representing embedded resources.
     *
     * @var array
     */
    protected $resources = array();

    /**
     * A collection of \HalLink objects keyed by the link relation to
     * this resource.
     *
     * array(
     *     'next' => [HalLink]
     * )
     *
     * @var array
     */
    protected $links = null;

    /**
     * A list of rel types for links that will force a rel type to array for one element
     *
     * @var array
     */
    protected $arrayLinkRels = array();

    /**
     * A list of rel types for links that will force a rel type to array for one element
     *
     * @var array
     */
    protected $arrayResourceRels = array();

    /**
     * Construct a new Hal object from an array of data. You can markup the
     * $data array with certain keys and values in order to affect the
     * generated JSON or XML documents if required to do so.
     *
     * '@' prefix on any array key will cause the value to be set as an
     * attribute on the XML element generated by the parent. i.e, array('x' =>
     * array('@href' => 'http://url')) will yield <x href='http://url'></x> in
     * the XML representation. The @ prefix will be stripped from the JSON
     * representation.
     *
     * Specifying the key 'value' will cause the value of this key to be set as
     * the value of the XML element instead of a child. i.e, array('x' =>
     * array('value' => 'example')) will yield <x>example</x> in the XML
     * representation. This will not affect the JSON representation.
     *
     * @param mixed $uri
     * @param array|Traversable $data
     *
     * @throws \RuntimeException
     */
    public function __construct($uri = null, $data = array())
    {
        $this->uri = $uri;

        if (!is_array($data) && !$data instanceof Traversable) {
            throw new RuntimeException(
                'The $data parameter must be an array or an object implementing the Traversable interface.');
        }
        $this->data = $data;

        $this->links = new HalLinkContainer();
    }

    /**
     * Decode a application/hal+json document into a Hal object.
     *
     * @param string $data
     * @param int $depth
     * @static
     * @access public
     * @return Hal
     */
    public static function fromJson($data, $depth = 0)
    {
//      18.02.2015 - php 5.2
//      return JsonHalFactory::fromJson(new static(), $data, $depth);

        $class = get_class(new FikenHal());
        return JsonHalFactory::fromJson(new $class(), $data, $depth);
    }

    /**
     * Add a link to the resource, identified by $rel, located at $uri.
     *
     * @param string $rel
     * @param string $uri
     * @param array $attributes
     *   Other attributes, as defined by HAL spec and RFC 5988.
     * @param bool $forceArray whether to force a rel to be an array if it has only one entry
     * @return Hal
     */
    public function addLink($rel, $uri, array $attributes = array(), $forceArray = false)
    {
        $this->links[$rel][] = new HalLink($uri, $attributes);

        if ($forceArray) {
            $this->arrayLinkRels[] = $rel;
        }

        return $this;
    }

    /**
     * Add an embedded resource, identified by $rel and represented by $resource.
     *
     * @param string $rel
     * @param Hal $resource
     *
     * @return Hal
     */
    public function addResource($rel, FikenHal $resource = null, $forceArray = true)
    {
        $this->resources[$rel][] = $resource;

        if ($forceArray) {
            $this->arrayResourceRels[] = $rel;
        }

        return $this;
    }

    /**
     * Set an embedded resource, identified by $rel and represented by $resource
     *
     * Using this method signifies that $rel will only ever be a single object
     * (only really relevant to JSON rendering)
     *
     * @param string $rel
     * @param FikenHal $resource
     */
    public function setResource($rel, $resource)
    {
        if (is_array($resource)) {
            foreach ($resource as $r) {
                $this->addResource($rel, $r);
            }

            return $this;
        }

        if (!($resource instanceof FikenHal)) {
            throw new InvalidArgumentException('$resource should be of type array or Hal');
        }

        $this->resources[$rel] = $resource;

        return $this;
    }

    /**
     * Set resource's data
     */
    public function setData(Array $data = null)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Return an array of data (key => value pairs) representing this resource.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return an array of HalLink objects representing resources
     * related to this one.
     *
     * @return array A collection of HalLink
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Lookup and return an array of HalLink objects for a given relation.
     * Will also resolve CURIE rels if required.
     *
     * @param string $rel The link relation required
     * @return array|bool
     *   Array of HalLink objects if found. Otherwise false.
     */
    public function getLink($rel)
    {
        return $this->links->get($rel);
    }


    //  18.02.2015 php 5.2
    public static function inner($resource){
        return is_array($resource) ? $resource : array($resource);
    }

    /**
     * Return an array of Hal objected embedded in this one.
     *
     * @return array
     */
    public function getResources()
    {
//        18.02.2015 php 5.2
//        $resources = array_map(function ($resource) {
//            return is_array($resource) ? $resource : array($resource);
//        }, $this->getRawResources());
//
        $resources = array_map(array("FikenHal", "inner"), $this->getRawResources());

        return $resources;
    }

    /**
     * Return an array of Hal objected embedded in this one. Each key
     * may contain an array of resources, or a single resource. For a
     * consistent approach, use getResources
     *
     * @return array
     */
    public function getRawResources()
    {
        return $this->resources;
    }

    /**
     * Get the first resource for a given rel. Useful if you're only expecting
     * one resource, or you don't care about subsequent resources
     *
     * @return FikenHal
     */
    public function getFirstResource($rel)
    {
        $resources = $this->getResources();

        if (isset($resources[$rel])) {
            return $resources[$rel][0];
        }

        return null;
    }

    /**
     * Set resource's URI
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Get resource's URI.
     *
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Return the current object in a application/hal+json format (links and
     * resources).
     *
     * @param bool $pretty
     *   Enable pretty-printing.
     * @param bool $encode
     *   Run through json_encode
     * @return string
     */
    public function asJson($pretty = false, $encode = true)
    {
        $renderer = new HalJsonRenderer();

        return $renderer->render($this, $pretty, $encode);
    }

    /**
     * Create a CURIE link template, used for abbreviating custom link
     * relations.
     *
     * e.g,
     * $hal->addCurie('acme', 'http://.../rels/{rel}');
     * $hal->addLink('acme:test', 'http://.../test');
     *
     * @param string $name
     * @param string $uri
     *
     * @return Hal
     */
    public function addCurie($name, $uri)
    {
        return $this->addLink('curies', $uri, array('name' => $name, 'templated' => true));
    }

    /**
     * Get a list of rel types for links that will be forced to an array for one element
     */
    public function getArrayLinkRels()
    {
        return $this->arrayLinkRels;
    }

    /**
     * Get a list of rel types for resources that will be forced to an array for one element
     */
    public function getArrayResourceRels()
    {
        return $this->arrayResourceRels;
    }
}



/**
 * This file is part of the Hal library
 *
 * (c) Ben Longden <ben@nocarrier.co.uk
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Nocarrier
 */

/**
 * HalJsonRenderer
 *
 * @uses HalRenderer
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
class HalJsonRenderer implements HalRenderer
{
    /**
     * Render.
     *
     * @param Hal $resource
     * @param bool $pretty
     * @return string
     */
    public function render(FikenHal $resource, $pretty, $encode = true)
    {
        $options = 0;
        if (version_compare(PHP_VERSION, '5.4.0') >= 0 and $pretty) {
            $options = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        }

        $arrayForJson = $this->arrayForJson($resource);
        if ($encode) {
            return stripcslashes(json_encode($arrayForJson, $options));
        }

        return $arrayForJson;
    }

    /**
     * Return an array (compatible with the hal+json format) representing
     * associated links.
     *
     * @param mixed $uri
     * @param array $links
     * @return array
     */
    protected function linksForJson($uri, $links, $arrayLinkRels)
    {
        $data = array();
        if (!is_null($uri)) {
            $data['self'] = array('href' => $uri);
        }
        foreach ($links as $rel => $links) {
            if (count($links) === 1 && $rel !== 'curies' && !in_array($rel, $arrayLinkRels)) {
                $data[$rel] = array('href' => $links[0]->getUri());
                foreach ($links[0]->getAttributes() as $attribute => $value) {
                    $data[$rel][$attribute] = $value;
                }
            } else {
                $data[$rel] = array();
                foreach ($links as $link) {
                    $item = array('href' => $link->getUri());
                    foreach ($link->getAttributes() as $attribute => $value) {
                        $item[$attribute] = $value;
                    }
                    $data[$rel][] = $item;
                }
            }
        }

        return $data;
    }

    /**
     * Return an array (compatible with the hal+json format) representing
     * associated resources.
     *
     * @param mixed $resources
     * @return array
     */
    protected function resourcesForJson($resources)
    {
        if (!is_array($resources)) {
            return $this->arrayForJson($resources);
        }

        $data = array();

        foreach ($resources as $resource) {
            $res = $this->arrayForJson($resource);

            if (!empty($res)) {
                $data[] = $res;
            }
        }

        return $data;
    }

    /**
     * Remove the @ prefix from keys that denotes an attribute in XML. This
     * cannot be represented in JSON, so it's effectively ignored.
     *
     * @param array $data
     *   The array to strip @ from the keys.
     * @return array
     */
    protected function stripAttributeMarker(array $data)
    {
        foreach ($data as $key => $value) {
            if (substr($key, 0, 5) == '@xml:') {
                $data[substr($key, 5)] = $value;
                unset ($data[$key]);
            } elseif (substr($key, 0, 1) == '@') {
                $data[substr($key, 1)] = $value;
                unset ($data[$key]);
            }

            if (is_array($value)) {
                $data[$key] = $this->stripAttributeMarker($value);
            }
        }

        return $data;
    }

    /**
     * Return an array (compatible with the hal+json format) representing the
     * complete response.
     *
     * @param Hal $resource
     * @return array
     */
    protected function arrayForJson(FikenHal $resource = null)
    {
        if ($resource == null) {
            return array();
        }

        $data = $resource->getData();
        $data = $this->stripAttributeMarker($data);

        $links = $this->linksForJson($resource->getUri(), $resource->getLinks(), $resource->getArrayLinkRels());
        if (count($links)) {
            $data['_links'] = $links;
        }

        foreach ($resource->getRawResources() as $rel => $resources) {
            $embedded = $this->resourcesForJson($resources);
            if (count($embedded) === 1 && !in_array($rel, $resource->getArrayResourceRels())) {
                $embedded = $embedded[0];
            }
            $data['_embedded'][$rel] = $embedded;
        }

        return $data;
    }
}



/**
 * This file is part of the Hal library
 *
 * (c) Ben Longden <ben@nocarrier.co.uk
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Nocarrier
 */

/**
 * The HalLink class
 *
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
class HalLink
{
    /**
     * The URI represented by this HalLink.
     *
     * @var string
     */
    protected $uri;

    /**
     * Any attributes on this link.
     *
     * array(
     *  'templated' => 0,
     *  'type' => 'application/hal+json',
     *  'deprecation' => 1,
     *  'name' => 'latest',
     *  'profile' => 'http://.../profile/order',
     *  'title' => 'The latest order',
     *  'hreflang' => 'en'
     * )
     *
     * @var array
     */
    protected $attributes;

    /**
     * The HalLink object.
     *
     * Supported attributes in Hal (specification section 5).
     *
     * @param string $uri
     *   The URI represented by this link.
     * @param array $attributes
     *   Any additional attributes.
     */
    public function __construct($uri, $attributes)
    {
        $this->uri = $uri;
        $this->attributes = $attributes;
    }

    /**
     * Return the URI from this link.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns the attributes for this link.
     *
     * return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * The string representation of this link (the URI).
     *
     * return string
     */
    public function __toString()
    {
        return $this->uri;
    }
}




/**
 * This file is part of the Hal library
 *
 * (c) Ben Longden <ben@nocarrier.co.uk
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Nocarrier
 */

/**
 * The HalLinkContainer class
 *
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
class HalLinkContainer extends ArrayObject
{
    /**
     * Retrieve a link from the container by rel. Also resolve any curie links
     * if they are set.
     *
     * @param string $rel
     *   The link relation required.
     * @return array|bool
     *   Link if found. Otherwise false.
     */
    public function get($rel)
    {
        if (array_key_exists($rel, $this)) {
            return $this[$rel];
        }

        if (isset($this['curies'])) {
            foreach ($this['curies'] as $link) {
                $prefix = strstr($link->getUri(), '{rel}', true);
                if (strpos($rel, $prefix) === 0) {
                    // looks like it is
                    $shortrel = substr($rel, strlen($prefix));
                    $attrs = $link->getAttributes();
                    $curie = "{$attrs['name']}:$shortrel";
                    if (isset($this[$curie])) {
                        return $this[$curie];
                    }
                }
            }
        }

        return false;
    }
}


/**
 * This file is part of the Hal library
 *
 * (c) Ben Longden <ben@nocarrier.co.uk
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Nocarrier
 */
/**
 * The Hal Renderer Interface
 *
 * @package Nocarrier
 * @author Ben Longden <ben@nocarrier.co.uk>
 */
interface HalRenderer
{
    /**
     * Render the Hal resource in the appropriate form.
     *
     * Returns a string representation of the resource.
     *
     * @param Hal $resource
     * @param boolean $pretty
     * @param boolean $encode
     */
    public function render(FikenHal $resource, $pretty, $encode = true);
}

class JsonHalFactory
{
    /**
     * Decode a application/hal+json document into a Hal object.
     *
     * @param string $text
     * @param int $depth
     * @static
     * @access public
     * @return Hal
     */
    public static function fromJson(FikenHal $hal, $text, $depth = 0)
    {
        list($uri, $links, $embedded, $data) = self::prepareJsonData($text);
        $hal->setUri($uri)->setData($data);
        self::addJsonLinkData($hal, $links);

        if ($depth > 0) {
            self::setEmbeddedResources($hal, $embedded, $depth);
        }

        return $hal;
    }

    /**
     * @param string $text
     */
    private static function prepareJsonData($text)
    {
        $data = json_decode($text, true);

        //18.02.2015  php 5.2
//        if (json_last_error() != JSON_ERROR_NONE) {
//            throw new RuntimeException('The $text parameter must be valid JSON');
//        }
        $uri = isset($data['_links']['self']['href']) ? $data['_links']['self']['href'] : "";
        unset ($data['_links']['self']);

        $links = isset($data['_links']) ? $data['_links'] : array();
        unset ($data['_links']);

        $embedded = isset($data['_embedded']) ? $data['_embedded'] : array();
        unset ($data['_embedded']);

        return array($uri, $links, $embedded, $data);
    }

    /**
     * @param FikenHal $hal
     */
    private static function addJsonLinkData($hal, $links)
    {
        foreach ($links as $rel => $links) {
            if (!isset($links[0]) or !is_array($links[0])) {
                $links = array($links);
            }

            foreach ($links as $link) {
                $href = $link['href'];
                unset($link['href'], $link['title']);
                $hal->addLink($rel, $href, $link);
            }
        }
    }

    /**
     * @param integer $depth
     */
    private static function setEmbeddedResources(FikenHal $hal, $embedded, $depth)
    {
        foreach ($embedded as $rel => $embed) {
            $isIndexed = array_values($embed) === $embed;
            $className = get_class($hal);
            if (!$isIndexed) {
                $hal->setResource($rel, self::fromJson(new $className, json_encode($embed), $depth - 1));
            } else {
                foreach ($embed as $resource) {
                    $hal->addResource($rel, self::fromJson(new $className, json_encode($resource), $depth - 1));
                }
            }
        }
    }
}






?>