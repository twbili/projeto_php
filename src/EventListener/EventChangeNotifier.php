<?php
namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class  EventChangeNotifier{

    public $inserts;
    public $updates;
    public $delations;
    public $collectionDeletions;
    public $collectionUpdates;

    public function __construct(private Security $security){

    }


    public function onFlush(OnFlushEventArgs $eventArgs)
    {

        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->inserts[] = $entity;

        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->updates[] = $entity;
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->logChange($entity,$em,'delete',$this->entityToArray($entity,$em));

        }

        foreach ($uow->getScheduledCollectionDeletions() as $col) {
            $this->collectionDeletions[] = $col;
        }

        foreach ($uow->getScheduledCollectionUpdates() as $col) {
            $this->collectionUpdates[] = $col;
        }
    }

    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getObjectManager();

        if($this->inserts){
            foreach ($this->inserts as $entity) {
                $this->logChange($entity,$em,'insert',$this->entityToArray($entity,$em));
            }
        }

        if($this->updates){
            foreach ($this->updates as $entity) {
                $uow = $em->getUnitOfWork();
                $changeSet = $uow->getEntityChangeSet($entity);
                $data=$this->rebuildUpdate($changeSet,$em);
                $this->logChange($entity,$em,'update',$data);
            }
        }
        if($this->collectionDeletions){
            foreach ($this->collectionDeletions as $col) {
                $clone=$col->getSnapshot();
                $this->collectionDeletions[]=[$col,$clone];
            }
        }

        if($this->collectionUpdates){
            foreach ($this->collectionUpdates as $col) {
                $clone=$col->getSnapshot();
                $this->collectionUpdates[]=[$col,$clone];
            }
        }
    }

    public function entityToArray($entity,$em):array{
        $array=[];
        $reflectionClass = new \ReflectionClass($entity);
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $property){
            $property->setAccessible(true);
            $value=$property->getValue($entity);
            $array[$property->getName()]=$this->resolveValueEntiy($value,$em);
        }
        return $array;
    }

    private function resolveValueEntiy ($value,$em){

        //Ver se é um objeto
        if(is_object($value) && $em->getMetadataFactory()->hasMetadataFor(get_class($value))){
            return method_exists($value,'getId')?$value->getId():null;
        }
        return $value;
    }

    public function logChange($entity,$em,$action,$data)
    {
        $query = 'INSERT INTO log (entity,action,date_time,changed_fields,user_id,entity_id) VALUES (?,?,?,?,?,?);';
        $smtp = $em->getConnection()->prepare($query);
        $smtp->bindValue(1,get_class($entity));
        $smtp->bindValue(2,$action);
        $smtp->bindValue(3,date('Y-m-d H:i:s'));
        $smtp->bindValue(4,json_encode($data));
        $smtp->bindValue(5,$this->security->getUser()->getId());
        $smtp->bindValue(6,$entity->getId());
        $smtp->executeStatement();
    }

    public function rebuildUpdate($changeSet,$em)
    {
        $reformattedChangeSet = [];

        foreach ($changeSet as $field=>$value){
            [$oldValue,$newValue]=$value;
            $oldValue=$this->resolveValueEntiy($oldValue,$em);
            $newValue=$this->resolveValueEntiy($newValue,$em);
            $reformattedChangeSet[$field]=[$oldValue,$newValue];
        }

        return $reformattedChangeSet;
    }

    private function logCollectionChange($em,$collection,$action)
    {
        $owner=$collection[0]->getOwner();
        $entityClass=get_class($owner);
        $mapping=$collection[0]->getMapping();
        $role=$mapping['fieldName'];
        $originalData=$collection[1];
        $currentData=$collection[0]->toArray();
        $originalIds= [];
        foreach ($originalData as $index=>$currentDatum){
            $originalIds[]=$this->resolveValueEntiy($currentDatum,$em);
        }
        $currentIds= [];
        foreach ($currentData as $index=>$currentDatum){
            $currentIds[]=$this->resolveValueEntiy($currentDatum,$em);
        }
        $data=[
            'role'=>$role,
            'original_ids'=>$originalIds,
            'current_ids'=>$currentIds,
        ];
        $this->logChange($em,$owner,$action,$data);

    }
}