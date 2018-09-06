<?php

namespace Frengky\Yupload\Concerns;

/**
 * Trait HasVirtualAttributes
 *
 * @property array $virtualAttributes
 * @property string $virtualAttributePrefix
 */
trait HasVirtualAttributes
{
    /** @var array */
    protected $virtualAttributes = [];

    /**
     * Override
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if ($attribute = $this->parseVirtualAttribute($key)) {
            $this->setVirtualAttributeValue($attribute, $value);
            return $this;
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * Override
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if ($attribute = $this->parseVirtualAttribute($key)) {
            return $this->getVirtualAttributeValue($attribute);
        }
        return parent::getAttribute($key);
    }

    /**
     * Add value to existing virtual attribute (changes to array)
     *
     * @param string $key
     * @param mixed $value
     */
    protected function addToVirtualAttribute($key, $value)
    {
        if (! isset($this->virtualAttributes[$key])) {
            $this->virtualAttributes[$key] = [];
        }
        $this->virtualAttributes[$key] = array_merge($this->virtualAttributes[$key], (array) $value);
    }

    /**
     * Get virtual attribute value
     *
     * @return mixed
     */
    protected function getVirtualAttributeValue($key)
    {
        return isset($this->virtualAttributes[$key]) ? $this->virtualAttributes[$key] : null;
    }

    /**
     * Set virtual attribute value
     *
     * @param mixed $key
     */
    protected function setVirtualAttributeValue($key, $value)
    {
        $this->virtualAttributes[$key] = $value;
    }

    /**
     * Get all virtual attributes key value pairs
     *
     * @return array
     */
    protected function getVirtualAttributes()
    {
        return $this->virtualAttributes;
    }

    /**
     * Clear all virtual attributes
     */
    protected function clearVirtualAttributes()
    {
        $this->virtualAttributes = [];
    }

    /**
     * Return the virtual attribute name ( skin from people_skin )
     *
     * @param string $attribute
     * @return string
     */
    protected function parseVirtualAttribute($attribute)
    {
        if (! isset($this->virtualAttributePrefix) || empty($this->virtualAttributePrefix))
            throw new \RuntimeException('$virtualAttributePrefix must be defined to use this trait');

        $prefix = $this->virtualAttributePrefix;
        if (strlen($attribute) > strlen($prefix) && strpos($attribute, $prefix) === 0) {
            return substr($attribute, strlen($prefix), strlen($attribute));
        }
        return '';
    }
}