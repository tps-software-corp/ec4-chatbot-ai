<?php

namespace Plugin\TPSChatbotAI\Repository;

use Eccube\Repository\AbstractRepository;
use Eccube\Repository\ProductRepository;
use Plugin\TPSChatbotAI\Entity\Config;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * ConfigRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TPSChatbotAIProductRepository extends ProductRepository
{
    public function searchProduct($criterias)
    {
        if (empty($criterias)) {
            return [];
        }
        $_q = [];
        foreach($criterias as $index => $criteria) {
            $_q['name_' . $index] = $criteria;
        }
        $builder = $this->createQueryBuilder('p')
            ->innerJoin('p.ProductClasses', 'pc')
            ->leftJoin('pc.ClassCategory1', 'cc1')
            ->leftJoin('pc.ClassCategory2', 'cc2')
            ->leftJoin('p.ProductImage', 'pi');
        $first = true;
        foreach($_q as $name => $value) {
            if ($first) {
                $builder = $builder->where("cc1.name LIKE :{$name}")->orWhere("cc2.name LIKE :{$name}");
                $first = false;
            } else {
                $builder = $builder->orWhere("cc1.name LIKE :{$name}")->orWhere("cc2.name LIKE :{$name}");
            }
        }
        $builder = $builder->andWhere('pc.visible = :visible');
        foreach($_q as $name => $value) {
            $builder = $builder->setParameter($name, '%'.$value.'%');
        }
            
        $products = $builder->setParameter('visible', true)
            ->getQuery()
            ->getResult()
            ;
        return $products;
    }
}
