<?php
namespace Kronofoto\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Interop\Container\ContainerInterface;

class ItemController 
{
    const FIS = [
        'id',
        'identifier',
        'collection_id',
        'latitude',
        'longitude',
        'year_min',
        'year_max',
        'is_published',
        'created', 
        'modified' 
    ];

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function read(Request $request, Response $response, array $args) 
    {
        $id = $args['id'];

        $conn = $this->container->db;

        $qBuilder = $conn->createQueryBuilder();

        $qBuilder
            ->select(
                'i.id',
                'i.identifier',
                'i.collection_id',
                'i.latitude',
                'i.longitude',
                'i.year_min',
                'i.year_max',
                'i.is_published',
                'i.created',
                'i.modified'
            )
            ->from('archive_item', 'i')
            ->where('i.id = :id')
            ->setParameter('id', $id);

        $stmt = $qBuilder->execute();
        $result = $stmt->fetch();

        if (!$result) {
            $error = array(
                'error' =>
                array(
                    'status' => '404',
                    'message' => 'Requested item not found',
                    'detail' => 'Invalid id'
                )
            );
            return $response->withJson($error, 404);
        }
        else {
            return $response->withJson($result);
        }
    }

    public function getItems(Request $request, Response $response, array $args) 
    {
        $conn = $this->container->db;

        $qBuilder = $conn->createQueryBuilder();

        $qParams = $request->getQueryParams();

        $qBuilder
            ->select(
                'i.id',
                'i.identifier',
                'i.collection_id',
                'i.latitude',
                'i.longitude',
                'i.year_min',
                'i.year_max',
                'i.is_published',
                'i.created',
                'i.modified'
            )
            ->from('archive_item', 'i')
            ->where('i.is_published = 1'); 

        $qs = new QueryStringHelper($qParams, self::FIELDS, $this->container);
        //
        //TODO this will need refactoring
        if ($qs->hasFilterParam()) {
            $filterParams = $qs->getFilterParams();
            foreach ($filterParams as $fp) {
                $field = $fp['field'];
                $value = $fp['value'];

                if ($field == 'collection_id') {
                    $qBuilder
                        ->andWhere(
                            $qBuilder->expr()->eq($field, ":$field"))
                            ->setParameter($field, "$value");
                }

                if ($field == 'year_min') {
                    $qBuilder
                        ->andWhere(
                            $qBuilder->expr()->lte($field, ":$field"))
                            ->setParameter($field, "$value");
                }

                if ($field == 'year_max') {
                    $qBuilder
                        ->andWhere(
                            $qBuilder->expr()->gte($field, ":$field"))
                            ->setParameter($field, "$value");
                }

                if ($field == 'identifier') {
                    $value .= '%';
                    $qBuilder
                        ->andWhere(
                            $qBuilder->expr()->like($field, ":$field"))
                            ->setParameter($field, "$value");
                }
            }
        }


        if ($qs->hasSortParam()) {
            $qBuilder
                ->orderBy($qs->getSortField(), $qs->getSortOrder());
        }

        $qBuilder
            ->setFirstResult($qs->getOffset())
            ->setMaxResults($qs->getLimit());

        $stmt = $qBuilder->execute();

        //print($qBuilder->getSql());
        $result = $stmt->fetchAll();

        return $response->withJson($result);
    }

    public function getDonorItems(Request $request, Response $response, array $args)
    {
        echo 'donor items';
    }

    public function getCollectionItems(Request $request, Response $response, array $args) 
    {
        echo 'collection items';
    }
}
