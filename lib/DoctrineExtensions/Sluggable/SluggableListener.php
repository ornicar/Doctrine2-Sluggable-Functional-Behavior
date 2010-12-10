<?php

namespace DoctrineExtensions\Sluggable;

use Doctrine\Common\EventArgs,
    Doctrine\Common\EventManager;

class SluggableListener
{
    /**
     * Constructor
     *
     * @param EventManager $evm Event Manager
     */
    public function __construct(EventManager $evm)
    {
        if (class_exists('\Doctrine\ORM\Events')) {
            $evm->addEventListener(\Doctrine\ORM\Events::prePersist, $this);
            $evm->addEventListener(\Doctrine\ORM\Events::preUpdate, $this);
        } else {
            $evm->addEventListener(\Doctrine\ODM\MongoDB\Events::prePersist, $this);
            $evm->addEventListener(\Doctrine\ODM\MongoDB\Events::preUpdate, $this);
        }
    }

    /**
     * preUpdate
     *
     * @param EventArgs $e Event
     */
    public function preUpdate(EventArgs $e)
    {
        $sluggable = $this->getSluggable($e);

        if (isset($sluggable) && $sluggable->getSlug() === null) {
            $this->getGenerator($e)->process($sluggable);
        }
    }
    /**
     * prePersist
     *
     * @param EventArgs $e Event
     */
    public function prePersist(EventArgs $e)
    {
        $sluggable = $this->getSluggable($e);

        if (isset($sluggable)) {
            $this->getGenerator($e)->process($sluggable);
        }
    }

    protected function getSluggable(EventArgs $e) {
        // ORM Entities
        if ($e instanceof \Doctrine\ORM\Event\LifecycleEventArgs) {
            $entity = $e->getEntity();
            if ($entity instanceof Sluggable) {
                return $entity;
            }
        }

        // ODM Documents
        if ($e instanceof \Doctrine\ODM\MongoDB\Event\LifecycleEventArgs){
            $document = $e->getDocument();
            if ($document instanceof Sluggable) {
                return $document;
            }
        }

        return null;
    }

    protected function getGenerator(EventArgs $e) {
     	  if ($e instanceof \Doctrine\ORM\Event\LifecycleEventArgs) {
            return new SlugGenerator($e->getEntityManager());
		  } else if ($e instanceof \Doctrine\ODM\MongoDB\Event\LifecycleEventArgs){
            return new SlugGenerator($e->getDocumentManager());
        }
    }
}
