<?php

/**
 *  simple class to bind a reference to a usage in a taxon or name
 * 
 * */
class ReferenceUsage{

    public String $id;
    public Reference $reference;
    public String $comment;
    public String $subjectType; 
    
    public function __construct($id, $ref, $comment, $subjectType){
        $this->id = $id;
        $this->reference = $ref;
        $this->comment = $comment;
        $this->subjectType = $subjectType;
    }

    public static function updateUsage($ref_kind, $link_uri, $display_text, $comment, $subject_type, $wfo, $reference_id){

        $response = new UpdateResponse("update reference", true, "Updating a reference");

        // we need the user
        $user = unserialize($_SESSION['user']);

        // the subject could be name or taxon
        $name = Name::getName($wfo);
        $placement = $subject_type == 'taxon' ? 1:0;

        // we overload the ref_kind to handle deletion
        if($ref_kind == 'DELETE' && $reference_id){
            $ref = Reference::getReference($reference_id);
            $name->removeReference($ref);
            return;
        }

        // how do we tell if they are updating or not?
        if($reference_id){

            // OK -we have a reference 
            // update the reference fields
            $ref = Reference::getReference($reference_id);
            $ref->setLinkUri($link_uri);
            $ref->setDisplayText($display_text);
            $ref->setKind($ref_kind);
            $ref->setUserId($user->getId());
            $ref->save();

            $response->children[] = new UpdateResponse("reference updated", true, "Existing reference $reference_id was updated");

            // is it in the subject?
            $subject_refs = $name->getReferences();
            foreach($subject_refs as $usage){
                if($usage->reference->getId() == $reference_id){
                    // found existing usage so just update it
                    $name->updateReference($ref, $comment, $placement);
                    $response->children[] = new UpdateResponse("usage updated", true, "Existing usage was updated");
                    return; // our work here is done
                }
            }
            
            // we haven't found the usage so add it as a new reference
            $name->addReference($ref, $comment, $placement);
            $response->children[] = new UpdateResponse("usage new", true, "A new usage was created");

            return;

        }else{
            // we have no reference so we need to create one
            // and the associated usage

            $ref = Reference::getReference(false);
            $ref->setLinkUri($link_uri);
            $ref->setDisplayText($display_text);
            $ref->setKind($ref_kind);
            $ref->setUserId($user->getId());
            $ref->save();
            $response->children[] = new UpdateResponse("reference new", true, "A new reference was was created: " . $ref->getId());
            $name->addReference($ref, $comment, $placement);
            $response->children[] = new UpdateResponse("usage new", true, "A new usage was created");

        }

        return $response;

    }

}