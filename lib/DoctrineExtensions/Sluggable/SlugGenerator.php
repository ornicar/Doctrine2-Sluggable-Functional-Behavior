<?php

namespace DoctrineExtensions\Sluggable;

use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata,
		Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata,
		Doctrine\ORM\EntityManager,
		Doctrine\ODM\MongoDB\DocumentManager;

class SlugGenerator
{
	/**
	 * @var Manager
	 */
	private $_m;

	/**
	 * Constructor
	 *
	 * @param Manager $em
	 */
	public function __construct($m)
	{
		$this->_m = $m;
	}

	/**
	 * Process an entity and generate a unique slug on the desired field.
	 *
	 * @todo The way we generate the slug is not optimal. It must be needed to deal with concurrency.
	 * @param Sluggable $entity Entity to be processed (implements Sluggable interface)
	 */
	public function process(Sluggable $entity)
	{
		// Retrieving ClassMetadata
		$class = $this->_m->getClassMetadata(get_class($entity));

		// Generate slug candidate
		$slugCandidate = $this->_getSlugCandidate($class, $entity);

		if ($this->_m instanceof EntityManager) {
			// Inspect storage for an already existent slug
			$qb = $this->_m->createQueryBuilder();
			$qb->select('COUNT(c.' . $entity->getSlugFieldName() . ')')
					->from($class->name, 'c')
					->where('c.' . $entity->getSlugFieldName() . ' LIKE ?1');
			$qb->setParameter(1, $slugCandidate . '%');
			$count = $qb->getQuery()->getSingleScalarResult();

			// If slug exists, append the counter (foo-2, for example)
			if (intval($count) > 0) {
				$slugCandidate .= '-' . $count;
			}

		} else if ($this->_m instanceof DocumentManager) {
			$tempCandidate = $slugCandidate;
			$count = $this->getSlugCandidateCount($slugCandidate, $entity);
			while ($this->getSlugCandidateCount($slugCandidate, $entity) > 0) {
				$slugCandidate = $tempCandidate . '-' . $count++;
			}
		}

		// Assign slug value into entity
		$class->setFieldValue($entity, $entity->getSlugFieldName(), $slugCandidate);
	}

	/**
	 * Get slug entry count
	 *
	 * @param string $slugCandidate
	 * @return int
	 */
	private function getSlugCandidateCount($slugCandidate, $entity) {
        return $this->_m->createQueryBuilder(get_class($entity))
            ->field($entity->getSlugFieldName())->equals(new \MongoRegex('/^' . preg_quote($slugCandidate, '/') . '/i'))
            ->getQuery()->count();
	}

	/**
	 * Generate a slug candidate given its ClassMetadata and an Entity to extract information.
	 *
	 * @param ClassMetadata $class Related Entity ClassMetadata
	 * @param Sluggable $entity Entity to have information extracted
	 * @return <type>
	 */
	private function _getSlugCandidate($class, Sluggable $entity)
	{
		$generatorFields = $entity->getSlugGeneratorFields();
		$slugCandidate = '';

		if ($generatorFields) {
			$slugCandidate = array();

			// Loop through all fields defined
			foreach ($generatorFields as $fieldName) {
				$slugCandidate[] = (string) $class->getReflectionProperty($fieldName)->getValue($entity);
			}

			$slugCandidate = implode(' ', $slugCandidate);
		} else {
			// TODO We do not have any field to be considered, create a unique hash using a URL shortener technique
			// Good reference (pt_BR): http://manoellemos.com/2009/11/23/zapt-in-entendendo-e-brincando-com-os-encurtadores-de-url/
		}

		$normalizer = new SlugNormalizer($slugCandidate);

		$rc = new \ReflectionClass(get_class($entity));
		$hasSlugDividerMethod = $rc->hasMethod('getSlugDivider');
		if ($hasSlugDividerMethod === TRUE) {
			$divider = $entity->getSlugDivider();
			$normalizer->setDivider($divider);
		}
		return $normalizer->normalize();
	}
}
