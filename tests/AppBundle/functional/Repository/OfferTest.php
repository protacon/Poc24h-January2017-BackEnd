<?php
declare(strict_types = 1);
/**
 * /tests/AppBundle/functional/Repository/OfferTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace AppBundle\functional\Repository;

use App\Entity\Offer as Entity;
use App\Repository\Offer as Repository;
use App\Tests\RepositoryTestCase;

/**
 * Class AuthorTest
 *
 * @package AppBundle\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class OfferTest extends RepositoryTestCase
{
    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $entityName = Entity::class;

    /**
     * @var array
     */
    protected $associations = [
        'workshop',
    ];

}
