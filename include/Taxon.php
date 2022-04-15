<?php

class Taxon extends WfoDbObject{


    // We need to be careful we run 
    // singletons on primary objects so only create
    // Taxa using factory methods.
    private ?Name $name = null;
    private ?Taxon $parent = null;

    private ?Array $children = null;
    private ?Array $synonyms = null;

    private ?Array $curatorIds = null;
    private ?Array $editors = null;
    
    private bool $isHybrid = false;

    protected static $loaded = array();



    /**
     * Create an instance of a Taxon
     * Don't call this directly use the getTaxonForName or getById or getRootTaxon
     * factory methods only.
     * 
     * @param int $db_id The primary key of the taxon in the database
     * If an array then set of DwC values.
     */
    public function __construct($db_id){
        if($db_id != -1){
            $this->id = $db_id;
            $this->load();
            self::$loaded[$this->id] = $this;
        }
    }

    /**
     * Will load the taxon from the db if it has a db id
     * Will throw error if the taxon doesn't have a db id yet
     * 
     */
    public function load(){

        global $mysqli;
        if(!$this->id) throw new ErrorException("You can't call load on a Taxon that doesn't have an db id yet");

        $sql = "SELECT * FROM taxa as t left join taxon_names as tn on t.id = tn.taxon_id WHERE t.id = {$this->id}";
        $result = $mysqli->query($sql);
        if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev
        // echo $sql; exit;
        $row = $result->fetch_assoc();

        // get the name via the taxonNames table
        if($row['name_id']) $this->name = Name::getName((int)$row['name_id']);

        // This is a good one. Be careful getting the parent because if you are at the root you are the parent. Infinite loop find you here after hours of searching.
        if($row['parent_id'] == $this->id){
            $this->parent = $this;
        }else{
            $this->parent = Taxon::getById($row['parent_id']);
        }

        $this->isHybrid = $row['is_hybrid'] > 0 ? true : false;
        $this->comment = $row['comment'];
        $this->issue = $row['issue'];
        $this->user_id = $row['user_id'];
        $this->created = $row['created'];
        $this->modified = $row['modified'];

        $result->close();

        // fixme - load synonyms?
    
    }

    /**
     * Fetch a taxon by its database id
     * 
     * @param int $taxon_id The primary key of the taxon in the taxa database table.
     * @return Taxon
     */
    public static function getById($taxon_id){
        
        if(isset(self::$loaded[$taxon_id])){
            return self::$loaded[$taxon_id];
        }

        return new Taxon($taxon_id);
        
    }

    /**
     * Returns the taxon associated with a name object
     * the name may be the accepted name or a synonym in this taxon
     *
     * @return Taxon or empty one if name isn't associated with the name.
     * 
     */
    public static function getTaxonForName($name){

        global $mysqli;

        if(!$name->getId()) throw new ErrorException("You can't call load on a Taxon with a Name that doesn't have an id i.e. has not been saved to the db");

        // is it in the names yet (we could possible check if it is in self::loaded before going to the db if this proves slow)
        $result = $mysqli->query("SELECT `taxon_id` FROM `taxon_names` WHERE `name_id` = {$name->getId()}");
        
        if($result->num_rows > 1) throw new ErrorException("Something terrible happened. There are multiple taxa with the same name id {$name->getId()}");
        
        // found a taxon for the name so return it
        if($result->num_rows == 1){
            $row = $result->fetch_assoc();
            return Taxon::getById($row['taxon_id']);
        }

        // can't get a taxon for this name so create an empty one and set this name in it
        // if they do checkIntegrity() or save() it will control for this name not being available
        $taxon = new Taxon(-1);
        $taxon->setAcceptedName($name);
        return $taxon;

    }

    /**
     * 
     * Returns the root taxon of all taxa
     * 
     */
    public static function getRootTaxon(){
        
        global $mysqli;
        
        // root taxon is the one which is its own parent 
        $result = $mysqli->query("SELECT `id` FROM `taxa` WHERE `id` = `parent_id` ");
        if($result->num_rows > 1) throw new ErrorException("Something terrible happened! There are multiple root taxa in the database.");
        if($result->num_rows < 1) throw new ErrorException("Something terrible happened! There is no root taxon in the database");

        $row = $result->fetch_assoc();

        return Taxon::getById($row['id']);

    
    }

    /*

        G E T T E R  &  S E T T E R  M E T H O D S

    */

    public function updateHybridStatus($is_hybrid){
        $this->setHybridStatus($is_hybrid);
        $this->save();

        if($is_hybrid){
            $this->getAcceptedName()->updateChangeLog("Set as hybrid");
        }else{
            $this->getAcceptedName()->updateChangeLog("Set as NOT hybrid");
        }
        
    }

    /**
     * Set if this is a hybrid or not
     * 
     * @param boolean True if it is a hybrid taxon
     */
    public function setHybridStatus($is_hybrid){
        $this->isHybrid = $is_hybrid;
    }

    public function getHybridStatus(){
        return $this->isHybrid;
    }

    /**
     * 
     * This is a wrapper around the same
     * method in the accepted name but adds in the hybrid status
     * 
     */
    public function getFullNameString($italics = true, $authors = true, $abbreviate_rank = true, $abbreviate_genus = false){

        // no name if we have no name
        if(!$this->getAcceptedName()) return "no name";

        // Insert X if any of our name parts are hybrid
        $fns = $this->getAcceptedName()->getFullNameString($italics, $authors, $abbreviate_rank, $abbreviate_genus);

        $genus_is_hybrid = false;
        $species_is_hybrid = false;

        if($this->name->getGenusString()){
            $ancestor = $this;
            while($ancestor = $ancestor->getParent()){

                if($ancestor->getRank() == 'species'){
                    $species_is_hybrid = $ancestor->getHybridStatus();
                }

                if($ancestor->getRank() == 'genus'){
                    $genus_is_hybrid = $ancestor->getHybridStatus();
                    break;
                }

            }
        }

        $hybrid_symbol = '× ';
        if($italics) $hybrid_symbol = "</i>$hybrid_symbol<i>";

        if($this->isHybrid){
            $n = $this->name->getNameString();
            $fns = str_replace($n, $hybrid_symbol . $n, $fns);
        }

        if($genus_is_hybrid){
            $n = $this->name->getGenusString();
            $fns = str_replace($n, $hybrid_symbol . $n, $fns);
        }


        if($species_is_hybrid){
            $n = $this->name->getSpeciesString();
            $fns = str_replace($n, $hybrid_symbol . $n, $fns);
        }    

        return $fns;


    }

    /**
     * Sets the name of this taxon.
     * 
     * @param Name A name object
     */

    public function setAcceptedName($name){
        $this->name = $name;
    }

    /**
     * Gets the accepted name of this taxon
     * 
     * @return Name the name object
     */
    public function getAcceptedName(){
        return $this->name;
    }

    public function setParent($parent){
        $this->getAcceptedName()->updateChangeLog("Child of: " . $parent->getAcceptedName()->getPrescribedWfoId());
        $this->parent = $parent;
    }
    
    public function getParent(){
        if($this->parent == $this){
            // we are the root
            return null;
        }else{
            return $this->parent;
        }
    }

    /**
     * Checks the overall integrity of the values set in the
     * Taxon. Will call other integrity check methods
     * 
     * @return Array An array of data about the integrity of the current values set in the Taxon
     * 
     */
    public function checkIntegrity(){

        global $mysqli;

        $integrity = new UpdateResponse('taxon', true, "Taxon integrity check");
        $integrity->status = WFO_INTEGRITY_OK;

        $integrity->children[] = $this->checkRank();
        $integrity->children[] = $this->checkAutonym();

        // Call integrity check on the accepted name?

        // is the name in use as the accepted name of another taxon?

        // I should be a correct lower rank to my parent

        // I should be same rank as my siblings

        
        foreach ($integrity->children as $check) {

            // one fails we all fail
            if(!$check->status == WFO_INTEGRITY_FAIL){
                $integrity->status = WFO_INTEGRITY_FAIL;
                $integrity->success = false;
                $integrity->message = "Failed on {$check->name}";
                break;
            }

            // if on is not success then we add a warning
            if(!$check->status != WFO_INTEGRITY_OK){
                $integrity->status = WFO_INTEGRITY_WARN;
                $integrity->success = true;
                $integrity->message = "Warning on {$check->name}";
            }

        }


        return $integrity;
    }

    /**
     * Checks the integrity of the rank set in the taxon.
     * and updates the supplied integrity array
     * Is this rank appropriate for the parent taxon?
     * Is this rank appropriate for the siblings of the taxon?
     * 
     * @param Array $integrity a summary of integrity so far
     * @return Array An updated version of the integrity
     */

    public function checkRank(){

        global $ranks_table;

        $integrity = new UpdateResponse('rank', true, 'Rank OK');
        $integrity->status = WFO_INTEGRITY_OK;

        // no parent no go
        if (!$this->parent){
            $integrity->success = false;
            $integrity->message = "No parent is defined so correct rank can't be ascertained.";
            return $integrity;
        }

        // we are root it is OK
        if($this->parent == $this){
            $integrity->message = "This is the root taxon so no rank evaluation needed.";
            return $integrity;
        }

        // we have a parent how does their rank compare to ours
        $parent_r = $this->parent->getRank();
        $my_rank = $this->getRank();

        // if the parent is the root then all ranks are permissible
        // otherwise we have to do some checking
        if($this->parent->getParent() != null){

                if(!$parent_r || !$my_rank){
                    //print_r($this->parent);
                    throw new ErrorException("No rank found for rank comparison. Parent rank: $parent_r. Taxon rank $my_rank.");
                    $integrity->status = WFO_INTEGRITY_FAIL;
                    $integrity->success = false;
                    $integrity->message = "No rank found for rank comparison. Parent rank: $parent_r. Taxon rank $my_rank.";
                    
                    return $integrity;
                }

                // check if we are of permissible rank to be a child of our parent
                $permissable  = $ranks_table[$parent_r]['children'];
                if(!in_array($my_rank, $permissable)){
                    $perms = implode(',', $permissable);
                    $integrity->status = WFO_INTEGRITY_FAIL;
                    $integrity->success = false;
                    $integrity->message = "You can't add a taxon of rank $my_rank to parent of rank $parent_r. Permissible ranks are $perms.";
                    return $integrity;
                }

        }        

        // got to here so we have good ranks
        $integrity->message = "Adding taxon of rank $my_rank to parent of rank $parent_r is permissible.";
        
        // we should be the same rank as our siblings
        $siblings = $this->parent->getChildren();
        $my_level = array_search($my_rank, array_keys($ranks_table));
        $higher_level_siblings = array();
        foreach ($siblings as $bro) {
            $bro_level = array_search($bro->getRank(), array_keys($ranks_table));
            if($bro_level < $my_level){
                $higher_level_siblings[$bro_level][] = $bro;
            }
        }
        // sort them so they are in kingdom -> form
        ksort($higher_level_siblings);
        $potential_parents = array_pop($higher_level_siblings);

        // we only need to worry about the lowest level (highest number) siblings 
        // imagine is we were a subform being added to a species and there were already subspecies and varieties
        // we'd be added to the autonym of the varieties. An autonym at subspecies level would be created 
        // when the autonym at variety level was created - so we don't need to worry about that here.
        // as being potential parents 
        // the act of creating a new parent will sort out anything beyond that.

        // if there are siblings with higher rank then throw a wobbly
        if(count($higher_level_siblings)){
            $integrity->status = WFO_RANK_REBALANCE;
            $integrity->success = false;
            $integrity->message = "There is an imbalance of ranks at this point in the hierarchy. These will be rebalanced on save.";
            $integrity->taxa = $potential_parents;
        }

        return $integrity;

    }

    /**
     * 
     * Taxa have a rank based on their name
     * 
     */
    public function getRank(){

        // we are a normal taxon
        if($this->name){
            return $this->name->getRank();
        }

        // we don't know what we are - probably an error!
        return null;

    }

    public function setRank($rank){
        if($this->name){
            return $this->name->setRank($rank);
        }
    }

    /**
     * An integrity check of whether an autonym is needed 
     * alongside this taxon and whether one is present or not.
     * 
     * 
     */
    public function checkAutonym(){

        global $ranks_table;
        global $mysqli;

        $integrity = new UpdateResponse('autonym', true, 'Autonym OK');
        $integrity->status = WFO_INTEGRITY_OK;

        // is this a subdivision of a genus or species?
        // if not return that autonym stuff is N/A
        $genus_index = array_search('genus', array_keys($ranks_table));
        $species_index = array_search('species', array_keys($ranks_table));
        $rank_index = array_search($this->getRank(), array_keys($ranks_table));
        
        if($rank_index <= $genus_index || $rank_index == $species_index){
            $integrity->status = WFO_AUTONYM_NA;
            $integrity->success = true;
            $integrity->message = "Autonyms are not applicable at the rank " . $this->getRank() . ".";
            return $integrity;
        }

        // Am I the autonym?
        if($this->isAutonym()){
            $integrity->status = WFO_AUTONYM;
            $integrity->success = true;
            $integrity->message = "This taxon is an autonym";
            return $integrity;
        }


        // if we are here then it has been established 
        // we are at the rank that autonyms occur and 
        // we are not an autonym 

        // does the autonym exist?
        $siblings = $this->parent->getChildren();
   
        foreach ($siblings as $bro) { 
            
            if($bro->isAutonym()){
                  // we have found the autonym amongst our siblings
                $integrity->status = WFO_AUTONYM_EXISTS;
                $integrity->success = true;
                $integrity->message = "There is an autonym at this level in the hierarchy.";
                $integrity->taxa[] = $bro;
                return $integrity;
              }
        }

        // can't find an autonym but there should be one
        $integrity->status = WFO_AUTONYM_REQUIRED;
        $integrity->success = true;
        $integrity->message = "Can't find an autonym but there should be one";
        $integrity->names[] = $this->findAutonymNames(
            $this->getAcceptedName()->getRank(), 
            $this->getAcceptedName()->getGenusString(), 
            $this->getAcceptedName()->getSpeciesString()
        );
        return $integrity;

    }

    /**
     * A wrapper around the function in the name
     * 
     */
    public function isAutonym(){
        if(!$this->name) return false;
        return $this->name->isAutonym();
    }

    /**
     * Does the work or save but should 
     * always be called via save (from WfoDbObject ) which wraps it in a db transaction.
     */
    protected function saveDangerously(){

        global $mysqli;
        global $ranks_table;

        if(!$this->canEdit()){
            throw new ErrorException("User does not have permission to save changes to Taxon");
            return;
        }

        // check validity and refuse to proceed if we aren't valid
        $integrity = $this->checkIntegrity();
        if($integrity->status == WFO_INTEGRITY_FAIL){
            $integrity->success = false;
            return $integrity;
        } 

        // Integrity checks out so it is OK to proceed

        // note setting of accepted name is done separately at the end
        // of the process to be sure we have a db id and that we can make
        // any other changes necessary in taxon_names

        if($this->id){

            // UPDATING
            // we have a db id so we are updating
            
            $stmt = $mysqli->prepare("UPDATE `taxa` 
                SET 
                `parent_id` = ?,
                `user_id` = ?,
                `is_hybrid` = ?,
                `comment` = ?, 
                `issue` = ?,
                `source` = ? 
                WHERE 
                `id` = ?"
            );
            if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev
            $parent_id = $this->parent->getId();
             $stmt->bind_param("iiisssi",
                $parent_id,
                $this->user_id,
                $this->isHybrid,
                $this->comment,
                $this->issue,
                $this->source,
                $this->id
            );
            if(!$stmt->execute()){
                throw new ErrorException($mysqli->error);
                $integrity->success = false;
                return $integrity;
            }

        }else{

            // CREATING
            // we don't have a db id so we are creating

             $stmt = $mysqli->prepare("INSERT 
                INTO `taxa` (`parent_id`, `user_id`, `is_hybrid`, `comment`,`issue`,`source`) 
                VALUES (?,?,?,?,?,?)");
            if($mysqli->error) echo $mysqli->error; // should only have prepare errors during dev
            $parent_id = $this->parent->getId();

            $stmt->bind_param("iiisss",
                $parent_id,
                $this->user_id,
                $this->isHybrid,
                $this->comment,
                $this->issue,
                $this->source
            );
            if(!$stmt->execute()){
                throw new ErrorException($mysqli->error);
                $integrity->success = false;
                return $integrity;
            }else{
                // get our db id
                $this->id = $mysqli->insert_id;
            }

        }

        // close the statement we opened in one of the legs above
        $stmt->close();

        // assign the accepted name whether we have created or updated
        $this->assignAcceptedName($this->name);

        // do we need to create an associated autonym?
        $autonym_integrity = null;
        foreach ($integrity->children as $check) {
            if($check->name = 'autonym') $autonym_integrity = $check;
        }
        if($autonym_integrity && $autonym_integrity->status == WFO_AUTONYM_REQUIRED){
            
            // create a name to base the taxon on
            $autonym = $this->createAutonym(
                $this->parent,
                $this->getRank()
            );
        
        } // end autonym



        // do we need to rebalance the tree at this point?
/*
        $rank_integrity = null;
        foreach ($integrity->children as $check) {
            if($check->name = 'rank'){
                $rank_integrity = $check;
                break;
            }
        }


        if($rank_integrity && $rank_integrity->status == WFO_RANK_REBALANCE){

            $potential_parents = $rank_integrity->taxa;

            // are any of them suitable parents?
            $new_parent = null;
            foreach ($potential_parents as $pot){
                if($pot->isAutonym()){
                    // we have a suitable parent.
                    $new_parent = $pot;
                    break;
                }
            }

            // if we haven't found a parent we need to create one
            if(!$new_parent){

                // different things above and below species level
                $genus_level = array_search('genus', array_keys($ranks_table));
                $my_level = array_search($this->getRank(), array_keys($ranks_table));

                if($my_level > $genus_level){
                    // below species level
                    $new_parent = $this->createAutonym($this->parent,  $potential_parents[0]->getRank());
                }

            }

            // OK we have the new parent - let's set it
            if($new_parent){
                $this->setParent($new_parent);
                $this->save();
                $this->load(); // will update the parents and synonyms
            }else{
                throw new ErrorException("Unable to set new parent for taxon {$this->id} in order to balance the tree.");
            }

        }
*/

        $integrity->success = true;
        return $integrity;

    }

    /**
     * Looks for suitable autonym names in the names table
     * 
     * @param String $rank The rank of the autonym name
     * @param String $genus The name string of the genus
     * @param String $species The (optional) species name string
     * @return Name[] An array of names found (we hope just one!)
     */
    private function findAutonymNames($rank, $genus, $species){

        global $mysqli;
        global $ranks_table;

        $out = array();

        $genus_index = array_search('genus', array_keys($ranks_table));
        $species_index = array_search('species', array_keys($ranks_table));
        $rank_index = array_search($rank, array_keys($ranks_table));

        if($rank_index > $species_index){
            
            // we are a subdivision of a species therefore name has to == species
            // it also has to be in this genus
            $sql = "SELECT id
                    from `names` 
                    where (length(`authors`) = 0 OR `authors` is null)
                    and `name` = `species`
                    and `genus` = '$genus'
                    and `species` = '$species'
                    and `rank` = '$rank'";
            $result = $mysqli->query($sql);
            while($row = $result->fetch_assoc()){
                $out[] = Name::getName($row['id']);
            }

        }else{
            
            // we are subdivision of a genus
            // the name is the same as the genus
            $result = $mysqli->query(
                "SELECT id
                    from `names` 
                    where (length(`authors`) = 0 OR `authors` is null)
                    and `name` = `genus`
                    and `genus` = '$genus'
                    and `rank` = '$rank'
            ");
            while($row = $result->fetch_assoc()){
                $out[] = Name::getName($row['id']);
            }

        }

        return $out;
    
    }

    /**
     * Creates a new autonym (Taxon) and possibly associated Name 
     * 
     * @param Taxon $parent The taxon that will be the parent of this autonym
     * @param String $rank The rank the autonym will be created at
     * 
     */
    private function createAutonym($parent, $rank){

        // see if we have a name
        $names = $this->findAutonymNames($rank, $parent->getAcceptedName()->getGenusString(), $parent->getAcceptedName()->getNameString());

        if(count($names) == 0){
            // We didn't find a name so create on
            $name = $this->createAutonymName($rank, $parent->getAcceptedName()->getGenusString(), $parent->getAcceptedName()->getNameString());
        }elseif(count($names) == 1){
            // we found a single name so can use that
            $name = $names[0];
        }else{
            // we found multiple names so throw a wobbly

            // FIXME - for import we just pick the first one!
            /*
            $c_ids = array();
            foreach($names as $c){
                $c_ids[] = $c->getId();
            }
            $c_ids = implode(",", $c_ids);

            throw new ErrorException("Searching for autonym name for {$this->parent->getId()}, {$this->getRank()} and found multiple candidates. These names need to be deduplicated. Name IDs are: $c_ids");
            return null;
            */
            error_log("Searching for autonym name for {$this->parent->getId()}, {$this->getRank()} and found multiple candidates. These names need to be deduplicated. Name IDs are: $c_ids");
            error_log("Picking first one");
            $name = $names[0];
        }

        // we have got to here so we must have a name and we know the parent
        // so we can return the autonym
        return $this->createAutonymTaxon($name, $parent);  

    }

    /**
     * Create a new Name object as an autonym
     * Used in conjunction with createAutonym and creatAutonymTaxon
     * 
     * @param String $rank the rank of the new name
     * @param String $genus the genus name string of the new Name
     * @param String $name The name string of the new Name (will be used as name string and/or species string depending on rank)
     * @return Name The new name or null on failure.
     */
    private function createAutonymName($rank, $genus, $name){

            global $ranks_table;

            $autonym_name = Name::getName(-1);

            // meta fields are quite simply copies or ours
            $autonym_name->setSource($this->getSource());
            $autonym_name->setUserId($this->getUserId());
            $autonym_name->setComment("Name automatically created to support autonym taxon.");

            // it has to be the same rank as us because it is an autonym next to us on the hierarchy
            $autonym_name->setRank($rank);
            $autonym_name->setGenusString($genus);

            // the other two strings depend on if we are above or below species level
            $species_level = array_search('species', array_keys($ranks_table));
            $our_level = array_search($rank, array_keys($ranks_table));

            if($our_level > $species_level){
                
                // we are below species - rank levels count up from Kingdom

                // the species is the same as ours as we are in this species
                $autonym_name->setSpeciesString($name);
            
                // the name of the autonym is the same as the species name
                $autonym_name->setNameString($name);
                

            }else{

                // we are above species level
                // the species isn't set - we are not in a species this is a subgenus or something.
                // the name is the same as the genus name
                $autonym_name->setNameString($name);


            } 

            // save the name
            $autonym_name->save();

            return $autonym_name;

    }

    /**
     * Creates a taxon based on an autonym name
     * 
     * @param Name $name  object that the autonym taxon will be based on (have as its accepted name)
     * @param Taxon $parent object that will be the parent of the new taxon
     * @return Taxon The new autonym taxon
     */
    private function createAutonymTaxon($name, $parent){

        $autonym = Taxon::getTaxonForName($name);

        // we can get it a fangle and create an autonym that already exists if the ranks are wrong - then we loop.
        // So don't save the taxon if it already exists. Only if it is new.
        if($autonym->getId() > 0) return null;

        $autonym->setSource($this->getSource());
        $autonym->setUserId($this->getUserId());
        $autonym->setComment("Taxon automatically created as autonym.");
        $autonym->setParent($parent);
        $autonym->save();
        $parent->load();
        return $autonym;
    }

    /**
     * Not to be confused associated public functions
     * 
     * This makes changes to the taxa table to map
     * a taxon_names row to the taxa.taxon_name_id field
     * 
     * This will call assignName first to make sure name is in table
     * 
     * 
     */
    private function assignAcceptedName($name){

        global $mysqli;

        if(!$this->canEdit()){
            throw new ErrorException("User does not have permission to save changes to Taxon");
            return;
        }

        $taxon_names_id = $this->assignName($name);

        if($taxon_names_id){
            $result = $mysqli->query("UPDATE taxa SET taxon_name_id = {$taxon_names_id} WHERE id = {$this->id}");
            return true;
        }else{
            // exceptions will have been thrown by assignName
            return false;
        }

    }


    public function addSynonym($name){
        // we might do some checking in the future
        $name->updateChangeLog("Synonym of: " . $this->getAcceptedName()->getPrescribedWfoId());
        return $this->assignName($name);
    }

    public function removeSynonym($name){

        // check the name isn't the accepted name
        if($this->getAcceptedName() == $name){
            throw new ErrorException("Trying to remove accepted name as if it were a synonym. name_id {$name->id} and taxon_id {$this->id}.");
            return false;
        }

        $name->updateChangeLog("Removed from taxonomy");

        // actually do it
        $this->unassignName($name);
    
    }

    public function delete(){

        global $mysqli;

        // we should have no children
        if(count($this->getChildren()) > 0){
            throw new ErrorException("Trying to delete a taxon that has children. name_id {$name->id} and taxon_id {$this->id}.");
            return false;
        }

        // we should have no synonyms        
        if(count($this->getSynonyms()) > 0){
            throw new ErrorException("Trying to delete a taxon that has synonyms. name_id {$name->id} and taxon_id {$this->id}.");
            return false;
        }

        // unplace my name
        $name = $this->getAcceptedName();
        $name->updateChangeLog("Removed from taxonomy");
        $this->unassignName($name);

        // delete my row
        $result = $mysqli->query("DELETE FROM taxa WHERE id = {$this->id}");
        if($mysqli->affected_rows == 1){
            return true;
        }else{
            throw new ErrorException("Failed to remove taxon {$this->id} no rows affected.");
            return false;
        }

    }

    /**
     * 
     * Makes necessary changes to taxon_names table 
     * to remove a name. Could be called when removing 
     * taxon or synonym
     * 
     */
    private function unassignName($name){

        global $mysqli;

        if(!$this->canEdit()){
            throw new ErrorException("User does not have permission to save changes to Taxon");
            return;
        }

        // we are extra cautious only remove a name if we own it and we do it by primary key
        $result = $mysqli->query("SELECT id FROM taxon_names WHERE name_id = {$name->id} AND taxon_id = {$this->id}");
        if($result->num_rows > 1) throw new ErrorException("Something terrible happened! There are multiple entries in taxon_names for name_id {$name->id} and taxon_id {$this->id}.");
        if($result->num_rows == 0){
            throw new ErrorException("Trying to remove name {$name->id} from taxon {$this->id} when that name isn't assigned to that taxon.");
        }else{
            $result = $mysqli->query("DELETE FROM taxon_names WHERE name_id = {$name->id} AND taxon_id = {$this->id}");
            if($mysqli->affected_rows == 1){
                return true;
            }else{
                throw new ErrorException("Failed to remove {$name->id} from taxon {$this->id} no rows affected.");
                return false;
            }
        }
    }

    /**
     * Not to be confused with public method setAcceptedName
     * This actually does the work of joining the names
     * up.
     * 
     * This can be used to assign accepted names and synonyms 
     * as it just makes necessary changes to the taxon_names table
     * 
     * @return int the id of the row in the taxon_names table or false on failure
     * 
     */
    private function assignName($name){
        
        global $mysqli;

        // we do nothing if the user doesn't have rights to change this taxon
        // They should never get here because interface should stop them
        if(!$this->canEdit()){
            throw new ErrorException("User does not have permission to save changes to Taxon");
            return;
        }

        // is the name already in use in the taxon_names table?
        $result = $mysqli->query("SELECT * FROM taxon_names WHERE name_id = {$name->id}");
        if($result->num_rows > 1) throw new ErrorException("Something terrible happened! There are multiple entries in taxon_names for name_id {$name->id}.");
        if($result->num_rows == 0){
            // the name is not assigned to any taxon we can go ahead and create the row
            $result = $mysqli->query("INSERT INTO taxon_names (taxon_id, name_id) VALUES ({$this->id}, {$name->id})");
            if($mysqli->affected_rows == 1){
                return $mysqli->insert_id;
            }else{
                throw new ErrorException("Failed to create taxon_names row for taxon_id {$this->id} and name_id {$name->id}");
                return false;
            }

        }else{

            // the name is assigned to something.
           // print_r($this);
            $row = $result->fetch_assoc();

            // is it us? If so nothing to do
            if($row['taxon_id'] == $this->id) return $row['id'];

            // it is not so we need to highjack it - but first we double check it isn't in use as an accepted name of another taxon
            $result = $mysqli->query("SELECT * FROM taxa WHERE taxon_name_id = {$row['id']} AND id != {$this->id}");
            if($result->num_rows > 0){
                // FIXME - during import of seed data we are just ignoring these
                error_log("Trying to assign taxon_name {$row['id']} to {$this->id} when it is already in use as an accepted taxon.");
                // throw new ErrorException("Trying to assign taxon_name {$row['id']} to {$this->id} when it is already in use as an accepted taxon.");
            }else{

                // now the highjack
                $mysqli->query("UPDATE taxon_names SET taxon_id = {$this->id} WHERE id = {$row['id']}");
                if($mysqli->affected_rows == 1){
                    return $row['id'];
                }else{
                    throw new ErrorException("Failed to update taxon_names row {$row['id']} for taxon_id {$this->id} and name_id {$name->id}");
                    return false;
                }

            }

        }


    }

    // ------------ R E L A T I O N S ----------------

    /**
     * We could do this in a more efficient manner but get it working
     * first. Just load all the kids
     * 
     */
    public function getChildren(){

        global $mysqli;

        // currently make no attempt to cache this list as 
        // when we are adding and removing kids it causes havoc
        // this call is quite cheap and objects may already be loaded.
        $this->children = array();
        $sql = "SELECT t.id FROM taxa as t
            join `taxon_names` as tn on t.`taxon_name_id` = tn.id
            join `names` as n on n.id = tn.`name_id`
            WHERE `parent_id` = {$this->id} 
            AND t.`id` != {$this->id}
            order by n.`name`";
        $result = $mysqli->query($sql); 
        if($mysqli->error){
            echo $mysqli->error;
            echo "\n$sql\n";
            //print_r($this);
        }
        while($row = $result->fetch_assoc()){
            $this->children[] = Taxon::getById($row['id']);
        }

        return $this->children;

    }

    public function getDescendants(){

        $descendants = array();

        $kids = $this->getChildren();

        foreach ($kids as $kid) {
            $descendants[] = $kid;
            $descendants = array_merge($descendants, $kid->getDescendants());
        }

        return $descendants;

    }

    public function getAncestors(){
        $ancestors = array();
        $dad = $this;
        while($dad = $dad->getParent()){
            $ancestors[] = $dad;
        }
        return $ancestors;
    }

    public function getAncestorAtRank($rank){
        $dad = $this;
        while($dad = $dad->getParent()){
            if($dad->getRank() == $rank) return $dad;
        }
        return null;
    }

    public function isRoot(){
        return $this->parent == $this;
    }

    public function getSynonyms(){

        global $mysqli;

        $this->synonyms = array();

        $sql = "SELECT name_id FROM taxon_names WHERE taxon_id = {$this->getId()}";

        // if we aren't root we exclude ourselves
        if($this->getAcceptedName()){
            $sql .= " and name_id != {$this->getAcceptedName()->getId()} ";
        }

        $result = $mysqli->query($sql); // FIXME should be in some order
        while($row = $result->fetch_assoc()){
            $this->synonyms[] = Name::getName($row['name_id']);
        }


        return $this->synonyms;

    }

    public function addCurator($user){

        global $mysqli;

        if(!$this->canEdit()){
            throw new ErrorException("User does not have permission to save changes to Taxon");
            return;
        }

        $response = new UpdateResponse('AddCurator', true, "Adding a curator id {$user->getId()} to taxon with id {$this->getId()}.");

        $sql = "INSERT INTO `users_taxa` (`user_id`, `taxon_id`) VALUES ( {$user->getId()}, {$this->getId()} );";
        $mysqli->query($sql);
        if($mysqli->error){
            error_log($mysqli->error);
            error_log($sql);
            $response->children[] = new UpdateResponse('AddCurator', false, $mysqli->error);
            $response->children[] = new UpdateResponse('AddCurator', false, $sql);
        } 

        // All curators are editors
        if(!$user->isEditor()){
            $user->setRole('editor');
            $user->save();
        }

        // force refresh of editors on next call
        $this->editors = null;
        $this->curatorIds = null;


        $response->consolidateSuccess();

        return $response;

    }

    public function removeCurator($user){

        global $mysqli;

        if(!$this->canEdit()){
            throw new ErrorException("User does not have permission to save changes to Taxon");
            return;
        }

        $response = new UpdateResponse('RemoveCurator', true, "Removing a curator id {$user->getId()} to taxon with id {$this->getId()}.");

        $sql = "DELETE FROM `users_taxa` WHERE `user_id` = {$user->getId()} AND `taxon_id` =  {$this->getId()};";
        $mysqli->query($sql);
        if($mysqli->error){
            error_log($mysqli->error);
            error_log($sql);
            $response->children[] = new UpdateResponse('RemoveCurator', false, $mysqli->error);
            $response->children[] = new UpdateResponse('RemoveCurator', false, $sql);
        }

        // if the user is no longer a curator of anything then they can't be an editor (unless they are a god)
        if(count($user->getTaxaCurated()) < 1 && !$user->isGod()){
                $user->setRole('nobody');
                $user->save();
        }

        // force refresh of editors on next call
        $this->editors = null;
        $this->curatorIds = null;

        $response->consolidateSuccess();
        return $response;

    }

    /**
     * This returns the ids of the curators
     *  these are specifically assigned to this taxon
     */
    public function getCuratorIds(){

        global $mysqli;

        if(!$this->curatorIds){
            $this->curatorIds = array();
            $sql = "SELECT `user_id` FROM `users_taxa` WHERE `taxon_id` = {$this->getId()}";
            $result = $mysqli->query($sql);
            while($row = $result->fetch_assoc()){
                $this->curatorIds[] = $row['user_id'];
            }
        } 

        return $this->curatorIds;
    
    }

    public function getCurators(){

        // if we haven't been saved then there are none
        if(!$this->getId()) return array();

        // this should used the cached lists if they are there
        $all = $this->getEditors();
        $curatorIds = $this->getCuratorIds();

        $out = array();
        foreach ($all as $e) {
            if(in_array($e->getId(), $curatorIds)) $out[] = $e;
        }
        return $out;


    }

    /**
     * This returns users who can edit this taxon
     * including the curators (owners) of the taxon
     */
    public function getEditors(){

        // if we haven't been saved then there are none
        if(!$this->getId()) return array();

        // editors are anyone who is a curator of this
        // taxon or any of its parent taxa

        if(!$this->editors){
            $ancestors = $this->getAncestors();
            array_unshift($ancestors, $this);  // we are not in our ancestors by default

            $curatorIds = array();
            foreach ($ancestors as $anc) {
                $curatorIds = array_merge($curatorIds, $anc->getCuratorIds());
            }
            $curatorIds = array_unique($curatorIds);

            $this->editors = array();
            foreach ($curatorIds as $id) {
                $this->editors[] = User::loadUserForDbId($id);
            }

            // be nice and sort alphabetically
            usort($this->editors, function($a, $b){
                $al = strtolower($a->getName());
                $bl = strtolower($b->getName());
                if ($al == $bl) return 0;
                return ($al > $bl) ? +1 : -1;
            });
        } 
        
        return $this->editors;

    }


    /**
     * Whether the user passed in is a curator of this
     * taxon or not.
     * 
     */
    public function isCurator($user){

        // if we haven't been saved to the db yet then answer is no
        if(!$this->getId()) return false;

        return in_array($user->getId(), $this->getCuratorIds());
    }

    public function canEdit(){

        $user = unserialize($_SESSION['user']);

        if($user->isGod()) return true; // gods can do anything.

        // if we haven't been saved to the db yet then answer is yes
        if(!$this->getId()) return true;

        // we can't be sure the user is the same object 
        // so we do it on ids
        $editors = $this->getEditors();
        foreach ($editors as $ed) {
            if($ed->getId() == $user->getId()) return true;
        }
        return false;

    }


} // Taxon