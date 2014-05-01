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
	// Actualiza el estatus a 'expire' de todas las facturas
	 function updatebillstatus($user_id){

		$em = $this->getEntityManager();
		$query = $em->createQuery("UPDATE HotelBillBundle:Bill r set r.billstatus = :expire 
		WHERE r.user = :user_id");
		$query->setParameter('user_id', $user_id);
		$query->setParameter('expire', 'expire');
		$numUpdated = $query->execute();
		$em->flush(); // Executes all updates.
        $em->clear(); // Detaches all objects from Doctrine!

		return 0;
	}// end updatebillstatus



	// Regresa el status de una reserva
	 function reser_status($reser_id){

		$em = $this->getEntityManager();
		$query = $em->createQuery("SELECT r.restatus as restatus FROM HotelRoomBundle:Reserve r 
		WHERE r.id = :reser_id");
		$query->setParameter('reser_id', $reser_id);
		$result = $query->execute();
		$em->flush(); // Executes all updates.
        $em->clear(); // Detaches all objects from Doctrine!

        $result['restatus'] = $result[0]['restatus'];

		return $result;
	}// end updatebillstatus



	// Genera la factura
	public function bill_generate($user_id, $reser_id){

		$em = $this->getEntityManager();

		$info_rese;	/* contiene la siguiente informacion referente a la reserva
				     * cantidad de dias
					 * tipo de habitacion
					 * categoria de la habitacion
					 * id de la habitacion
					**/

		$cost_habi_t; /* contiene el precio del tipo de habitacion **/

		$cost_habi_c; /* contiene el precio de la categoria de habitacion **/		
		
		$num_minibar; /* contiene la cantidad de cada item en el minibar asociado a la reserva **/

		$des_minibar; /* contiene la descripcion de cada item en el minibar asociado a la reserva **/

		$items_cost = 0;

		$cost_childbed;	

		$phonecall; /* variables utilizadas para calcular las llamdas asociada a la reserva **/
		$call_i_cost;
		$call_n_cost;
        $num_calls_int = 0;
        $num_calls_nac = 0;
        $total_int = 0;
        $total_nac = 0;
        $mins_total = 0;					   	


        $result;

        // Consulta informacion de la reserva
        //-----------------------------------------------------------------------------------------------//

			$query = $em->createQuery('SELECT DATEDIFF(r.exitdate, r.entrydate) AS days, r.roomtype AS type,
			 r.roomcategory AS category, r.childbed AS num_childbed, IDENTITY(r.room) AS room_id 
			 FROM HotelRoomBundle:Reserve r  WHERE  r.id = :id');		
			$query->setParameter('id', $reser_id);
			$info_rese = $query->getResult();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!


        // Consulta el precio del tipo de habitacion
        //-----------------------------------------------------------------------------------------------//

			$query = $em->createQuery('SELECT r.price AS type_cost FROM HotelRoomBundle:ConsumableStore r
			  WHERE  r.name = :name');		
			$query->setParameter('name', $info_rese[0]['type']);
			$cost_habi_t = $query->getResult();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!


        // Consulta el precio de la categoria de la habitacion
 		//-----------------------------------------------------------------------------------------------//

			$query = $em->createQuery('SELECT r.price AS category_cost FROM HotelRoomBundle:ConsumableStore r
			  WHERE  r.name = :name');	
			$query->setParameter('name', $info_rese[0]['category']);
			$cost_habi_c = $query->getResult();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!



 		//-----------------------------------------------------------------------------------------------//
		    	$newbill = $em->getRepository('HotelBillBundle:Bill')->findOneBy(
		       		array(
		       		'user' => $user_id,
		       		'billstatus' => 'actual'
		       	));

		    $aux_typebill = $newbill->getTypeBill();
	// si la reserva es ocupada
	if ($aux_typebill == 'complete'){



      	// Cantidad de cada item en el minibar
 		//-----------------------------------------------------------------------------------------------//

			$query = $em->createQuery('SELECT  IDENTITY(r.consumablestore) AS consumablestore, r.amount AS amount
				FROM HotelRoomBundle:Consumable r WHERE  r.reserve = :reserve ORDER BY r.consumablestore ASC ');
			$query->setParameter('reserve', $reser_id);
			$num_minibar = $query->getResult();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!

			$result['num_minibar'] = (count($num_minibar));
			$num_items = (count($num_minibar));


        // Consulta las llamadas telefonicas pertenecientes a la reserva
 		//-----------------------------------------------------------------------------------------------//
 
			$query = $em->createQuery('SELECT p.starttime AS starttime, p.endtime AS endtime, p.calltype AS calltype 
				FROM HotelRoomBundle:PhoneCall p
			    WHERE  p.reserve = :reserve');	
			$query->setParameter('reserve', $reser_id);
			$phonecall = $query->getResult();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!      


	    // Consulta el precio de las cama de niño adicional
 		//-----------------------------------------------------------------------------------------------//

			$query = $em->createQuery('SELECT r.price AS cost_childbed FROM HotelRoomBundle:ConsumableStore r
			  WHERE  r.name = :name');	
			$query->setParameter('name', 'cama_niño');
			$cost_childbed = $query->getResult();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!


	    // Consulta el precio de las llamadas internacionales  y nacionales
 		//-----------------------------------------------------------------------------------------------//

          if(count($phonecall) > 0){

			$query = $em->createQuery('SELECT r.price AS call_i_cost FROM HotelRoomBundle:ConsumableStore r
			  WHERE  r.name = :name');	
			$query->setParameter('name', 'llamada_internacional');
			$call_i_cost = $query->getResult();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!

			$query = $em->createQuery('SELECT r.price AS call_n_cost FROM HotelRoomBundle:ConsumableStore r
			  WHERE  r.name = :name');	
			$query->setParameter('name', 'llamada_nacional');
			$call_n_cost = $query->getResult();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!	
       
           }

        //recorrido por la lista de llamadas calculando su precio total y tiempo total en minutos
 		//-----------------------------------------------------------------------------------------------//

           	$num_call = count($phonecall);
           	for ($i=0; $i < $num_call; $i++) { 

           	  // Tiempo de la llamada (hora y minutos )
		      $stmt = $this->getEntityManager()
		                   ->getConnection()
		                   ->prepare('SELECT TIMEDIFF(:endtime, :starttime) as time');
		      $stmt->bindValue('endtime', $phonecall[$i]['endtime']->format('Y-m-d H:i:s'));
		      $stmt->bindValue('starttime', $phonecall[$i]['starttime']->format('Y-m-d H:i:s'));
		      $stmt->execute();
		      $rs_time = $stmt->fetchAll();  
	          
	          // cantidad de horas de la llamada
		      $stmt = $this->getEntityManager()
		                   ->getConnection()
		                   ->prepare('SELECT HOUR(:hour) as hour');
		      $stmt->bindValue('hour', $rs_time[0]['time']);
		      $stmt->execute();
		      $rs_hour = $stmt->fetchAll();  
		   
		   	  // cantidad de minutos de la llamada
		      $stmt = $this->getEntityManager()
		                   ->getConnection()
		                   ->prepare('SELECT MINUTE(:minute) as minute');
		      $stmt->bindValue('minute', $rs_time[0]['time']);
		      $stmt->execute();
		      $rs_minute = $stmt->fetchAll(); 

		      // minutos total
           	  $mins_total = 60*$rs_hour[0]['hour'] + $rs_minute[0]['minute'];

                //cuando la llamada no dura más de un minuto
                if($mins_total == 0)    
                	$mins_total = 1;
                
                //acumulando el precio total de la llamada
                if($phonecall[$i]['calltype'] == 'international'){//internacional
                    $total_int += $mins_total * $call_i_cost[0]['call_i_cost'];
                    $num_calls_int++;
                }else{//nacional
                    $total_nac += $mins_total * $call_n_cost[0]['call_n_cost'];
                    $num_calls_nac++;
                }                

           	}


        // Consulta las descripcion de los items del minibar en el almacen
 		//-----------------------------------------------------------------------------------------------//

	        $query = $em->createQuery('SELECT r.price AS price, r.id AS id,
	        r.name AS name, r.brand AS brand FROM HotelRoomBundle:ConsumableStore r WHERE r.id IN
	        	( 
	        	SELECT IDENTITY(rc.consumablestore) FROM HotelRoomBundle:Consumable rc 
	        	WHERE rc.reserve = :reserve
	        	) 
	        ORDER BY r.id ASC ');
			$query->setParameter('reserve', $reser_id);
			$des_minibar = $query->getResult();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!


 		//-----------------------------------------------------------------------------------------------//
        //				      EMPEZANDO ARMAR LA FACTURA.  CREANDO Y AGREGANDO LOS ITEMS DE LA FACTURA  			        //
 		//-----------------------------------------------------------------------------------------------//


        // Le asigna las llamadas internacionales a la factura, asocinado los BillItems (recien creados)
        // a la factura.
			if( $num_calls_int > 0){          

		    	$newbill = $em->getRepository('HotelBillBundle:Bill')->findOneBy(
		       		array(
		       		'user' => $user_id,
		       		'billstatus' => 'actual'
		       	)); 		
	        
	    		$billitems = new BillItems();
	    		$billitems->setBill($newbill);
	    		$billitems->setName('llamadas internacionales');
	    		$billitems->setPrice($total_int);
	    		$billitems->setAmount($num_calls_int);
	    		$billitems->setRoomId($info_rese[0]['room_id']);
	    		$em->persist($billitems);
	        	$em->flush();
	        	$em->clear(); // Detaches all objects from Doctrine!

	        	$items_cost = $items_cost + $total_int;	
			}
		

        // Le asigna las llamadas nacionales a la factura, asocinado los BillItems (recien creados)
        // a la factura.
			if( $num_calls_nac > 0){       
	    	
		    	$newbill = $em->getRepository('HotelBillBundle:Bill')->findOneBy(
		       		array(
		       		'user' => $user_id,
		       		'billstatus' => 'actual'
		       	)); 

	    		$billitems = new BillItems();
	    		$billitems->setBill($newbill);
	    		$billitems->setName('llamadas nacionales');
	    		$billitems->setPrice($total_nac);
	    		$billitems->setAmount($num_calls_nac);
	    		$billitems->setRoomId($info_rese[0]['room_id']);
	    		$em->persist($billitems);
	       		$em->flush();
	       		$em->clear(); // Detaches all objects from Doctrine!

	       		$items_cost = $items_cost +  $total_nac;
	    
			}

		
		// Agregando el item de cama de niño (precio y cantidad (si existe))
			if ( $info_rese[0]['num_childbed'] > 0 ) {

		    	$newbill = $em->getRepository('HotelBillBundle:Bill')->findOneBy(
		       		array(
		       		'user' => $user_id,
		       		'billstatus' => 'actual'
		       	)); 

	    		$billitems = new BillItems();
	    		$billitems->setBill($newbill);
	    		$billitems->setName('cama de niño');
	    		$billitems->setPrice($cost_childbed[0]['cost_childbed'] * $info_rese[0]['num_childbed'] );
	    		$billitems->setAmount($info_rese[0]['num_childbed']);
	    		$billitems->setRoomId($info_rese[0]['room_id']);
	    		$em->persist($billitems);
	       		$em->flush();
	       		$em->clear(); // Detaches all objects from Doctrine!			

	       		$items_cost = $items_cost + ($cost_childbed[0]['cost_childbed'] * $info_rese[0]['num_childbed']);

			}


	    // Consulta la factura recien agregada
	    	$newbill = $em->getRepository('HotelBillBundle:Bill')->findOneBy(
	       		array(
	       		'user' => $user_id,
	       		'billstatus' => 'actual'
	       	));      
                              
        // Le asigna todos los items del minibar a la factura, asocinado los BillItems (recien creados)
        // a la factura.
			for ($i = 0; $i < $num_items; ++$i) {
	    		$billitems = new BillItems();
	    		$billitems->setBill($newbill);
	    		$billitems->setName($des_minibar[$i]['name']. ' - ' .$des_minibar[$i]['brand']);
	    		$billitems->setPrice($des_minibar[$i]['price']*$num_minibar[$i]['amount']);
	    		$billitems->setAmount($num_minibar[$i]['amount']);
	    		$billitems->setRoomId($info_rese[0]['room_id']);
	    		$em->persist($billitems);

	    		$items_cost = $items_cost + ($des_minibar[$i]['price']*$num_minibar[$i]['amount']);
	   		
	   			 if ($i  == $num_items - 1) {
	        		$em->flush();
	        		$em->clear(); // Detaches all objects from Doctrine!
	    		}

			}

     }// fin del if

   		// Asigna las caracteristicas de la factura a la entidad Bill (factura) ya creada.

 	    	$newbill = $em->getRepository('HotelBillBundle:Bill')->findOneBy(
	       		array(
	       		'user' => $user_id,
	       		'billstatus' => 'actual'
	       	));     

	    	$newbill->setItemsCost($items_cost);
	    	$newbill->setTotalCost($items_cost + ($info_rese[0]['days'] * 
	        	( $cost_habi_t[0]['type_cost'] * $cost_habi_c[0]['category_cost']) ));

	        $newbill->setCategory($info_rese[0]['category']);
	        $newbill->setCategoryCost($cost_habi_c[0]['category_cost']);
	        $newbill->setType($info_rese[0]['type']);
	        $newbill->setTypeCost($cost_habi_t[0]['type_cost']);
	        $newbill->setHousingDays($info_rese[0]['days']);
	        $newbill->setHousingCost($info_rese[0]['days'] * 
	        	( $cost_habi_t[0]['type_cost'] * $cost_habi_c[0]['category_cost']) );  


	// si la reserva es cancelada		
	if($aux_typebill == 'canceled') {


			$query = $em->createQuery('SELECT DATEDIFF(r.entrydate, CURRENT_DATE() ) AS days 
			 FROM HotelRoomBundle:Reserve r  WHERE  r.id = :id');		
			$query->setParameter('id', $reser_id);
			$info_rese_c = $query->getResult();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!	        

                if($info_rese_c[0]['days']  == 0){//100%
                    $newbill->setFailCost(" 100%");
                }elseif($info_rese_c[0]['days'] == 1){//30% 1 día antes
                    $newbill->setHousingCost( $newbill->getHousingCost() * (0.30) );
                    $newbill->setFailCost(" 30%");
                }elseif($info_rese_c[0]['days'] >= 2 && 
                        $info_rese_c[0]['days'] <= 4){//10% 2-5 días antes
                   $newbill->setHousingCost( $newbill->getHousingCost() * (0.10) );
                    $newbill->setFailCost(" 10%");
                }else{// > 5 días 0%
                    $newbill->setHousingCost(0);
                    $newbill->setFailCost(" 0%");
                }

	}// end else


		// Actualizar estatus de la habitacion (ocupada a libre)
 		//-----------------------------------------------------------------------------------------------//

			$em = $this->getEntityManager();
			$query = $em->createQuery("UPDATE HotelRoomBundle:Room r set r.roomstatus = :roomstatus 
			WHERE r.id = :id ");
			$query->setParameter('id', $info_rese[0]['room_id']);	
			$query->setParameter('roomstatus', 'free');
			$numUpdated = $query->execute();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!
	

		// Actualizar estatus de la reserva (ocupada a completada) o ( activa a cancelada)
 		//-----------------------------------------------------------------------------------------------//

			$em = $this->getEntityManager();
			$query = $em->createQuery("UPDATE HotelRoomBundle:Reserve r set r.restatus = :restatus_new 
			WHERE r.user = :user_id AND r.restatus = :restatus_old AND r.id = :reser_id ");
			$query->setParameter('user_id', $user_id);
			$query->setParameter('reser_id', $reser_id);

				// si la reserva esta ocupada
				if ($aux_typebill == 'complete'){
					$query->setParameter('restatus_old', 'occupied');			
					$query->setParameter('restatus_new', 'complete');
				}else{ // si la reserva fue cancelada
					$query->setParameter('restatus_old', 'active');			
					$query->setParameter('restatus_new', 'canceled');
				}

			$numUpdated = $query->execute();
			$em->flush(); // Executes all updates.
	        $em->clear(); // Detaches all objects from Doctrine!
	

		return $result;
	} //end bill_generate



	// Consulta todos los items asociado a una factura actual
	 function billitems($user_id){

		$em = $this->getEntityManager();


        // Consulta la factura recien agregada
    	$newbill = $em->getRepository('HotelBillBundle:Bill')->findOneBy(
       		array(
       		'user' => $user_id,
       		'billstatus' => 'actual'
       	)); 

		$query = $em->createQuery('SELECT b  FROM HotelBillBundle:BillItems b WHERE b.bill = :bill');
		$query->setParameter('bill', $newbill->getId());
		$aux = $query->getResult();
		$em->flush(); // Executes all updates.
        $em->clear(); // Detaches all objects from Doctrine!

		return $aux;
	}// end updatebillstatus


}//end