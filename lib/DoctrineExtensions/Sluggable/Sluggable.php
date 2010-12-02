<?php

namespace DoctrineExtensions\Sluggable;

interface Sluggable
{
    /**
     * Retrieves the slug field name
     *
     * @return string
     */
    function getSlugFieldName();

    /**
     * Retrieves the slug
     *
     * @return string
     */
    function getSlug();

    /**
     * Retrieves the Entity fields used to generate the slug value
     *
     * @return array
     */
    function getSlugGeneratorFields();
}
