<?php

namespace Hotel\BillBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * BillRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BillRepository extends EntityRepository
{
	// actualiza el estatus a 'expire' de todas las facturas
	public function updatebillstatus($user_id){

		$em = $this->getEntityManager();
		$query = $em->createQuery("UPDATE HotelBillBundle:Bill r set r.billstatus = :expire WHERE r.user = :user_id");
		$query->setParameter('user_id', $user_id);
		$query->setParameter('expire', 'expire');
		$numUpdated = $query->execute();
		$em->flush(); // Executes all updates.
        $em->clear(); // Detaches all objects from Doctrine!
        
		return $numUpdated;
	} 


}