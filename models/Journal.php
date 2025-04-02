<?php
// models/Journal.php - Journal model
class Journal {
    private $conn;
    private $table = 'journal_entries';
    private $references_table = 'journal_references';
    
    // Journal properties
    public $id;
    public $user_id;
    public $title;
    public $content;
    public $mood;
    public $entry_date;
    public $created_at;
    public $updated_at;
    public $references = [];
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new journal entry
    public function create() {
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));
        
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  SET user_id = :user_id, 
                      title = :title, 
                      content = :content,
                      mood = :mood,
                      entry_date = :entry_date";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':content', $this->content);
        $stmt->bindParam(':mood', $this->mood);
        $stmt->bindParam(':entry_date', $this->entry_date);
        
        // Execute query
        if($stmt->execute()) {
            // Get the ID of the newly created entry
            $this->id = $this->conn->lastInsertId();
            
            // Add references if any
            if(!empty($this->references)) {
                $this->addReferences();
            }
            
            return true;
        }
        
        return false;
    }
    
    // Add references to a journal entry
    private function addReferences() {
        // Create query
        $query = "INSERT INTO " . $this->references_table . " 
                  (journal_id, reference_type, reference_id) 
                  VALUES (:journal_id, :reference_type, :reference_id)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind journal ID parameter
        $stmt->bindParam(':journal_id', $this->id);
        
        // Loop through references and add them
        foreach($this->references as $reference) {
            // Bind other parameters
            $stmt->bindParam(':reference_type', $reference['type']);
            $stmt->bindParam(':reference_id', $reference['id']);
            
            // Execute query
            $stmt->execute();
        }
    }
    
    // Get journal entry by ID
    public function getJournalById($id) {
        // Query for journal entry
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id = :id 
                  LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':id', $id);
        
        // Execute query
        $stmt->execute();
        
        // Get row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->title = $row['title'];
            $this->content = $row['content'];
            $this->mood = $row['mood'];
            $this->entry_date = $row['entry_date'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            // Get references
            $this->getReferences();
            
            return true;
        }
        
        return false;
    }
    
    // Get references for a journal entry
    private function getReferences() {
        // Query for references
        $query = "SELECT r.reference_type, r.reference_id,
                  CASE 
                      WHEN r.reference_type = 'habit' THEN (SELECT title FROM habits WHERE id = r.reference_id)
                      WHEN r.reference_type = 'goal' THEN (SELECT title FROM goals WHERE id = r.reference_id)
                      WHEN r.reference_type = 'challenge' THEN (SELECT title FROM challenges WHERE id = r.reference_id)
                      ELSE NULL
                  END as reference_title
                  FROM " . $this->references_table . " r 
                  WHERE r.journal_id = :journal_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':journal_id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Get results
        $this->references = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->references[] = [
                'type' => $row['reference_type'],
                'id' => $row['reference_id'],
                'title' => $row['reference_title']
            ];
        }
    }
    
    // Get all journal entries for a user
    public function getAllJournals($user_id, $limit = null, $offset = 0) {
        // Query for journal entries
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  ORDER BY entry_date DESC";
        
        // Add limit if specified
        if($limit !== null) {
            $query .= " LIMIT :offset, :limit";
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        
        if($limit !== null) {
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $journals = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Create a temporary journal object to get references
            $temp = new Journal($this->conn);
            $temp->id = $row['id'];
            $temp->getReferences();
            
            // Format journal data
            $journals[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'content' => $row['content'],
                'mood' => $row['mood'],
                'entry_date' => $row['entry_date'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'references' => $temp->references
            ];
        }
        
        return $journals;
    }
    
    // Search journal entries for a user
    public function searchJournals($user_id, $search_term) {
        // Query for journal entries
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  AND (title LIKE :search_term OR content LIKE :search_term) 
                  ORDER BY entry_date DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        $search_param = '%' . $search_term . '%';
        $stmt->bindParam(':search_term', $search_param);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $journals = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Create a temporary journal object to get references
            $temp = new Journal($this->conn);
            $temp->id = $row['id'];
            $temp->getReferences();
            
            // Format journal data
            $journals[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'content' => $row['content'],
                'mood' => $row['mood'],
                'entry_date' => $row['entry_date'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'references' => $temp->references
            ];
        }
        
        return $journals;
    }
    
    // Update a journal entry
    public function update() {
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->content = htmlspecialchars(strip_tags($this->content));
        
        // Create query
        $query = "UPDATE " . $this->table . " 
                  SET title = :title, 
                      content = :content,
                      mood = :mood,
                      entry_date = :entry_date
                  WHERE id = :id AND user_id = :user_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':content', $this->content);
        $stmt->bindParam(':mood', $this->mood);
        $stmt->bindParam(':entry_date', $this->entry_date);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        if($stmt->execute()) {
            // Update references
            $this->updateReferences();
            
            return true;
        }
        
        return false;
    }
    
    // Update references for a journal entry
    private function updateReferences() {
        // First delete all existing references
        $query = "DELETE FROM " . $this->references_table . " WHERE journal_id = :journal_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':journal_id', $this->id);
        $stmt->execute();
        
        // Then add new references if any
        if(!empty($this->references)) {
            $this->addReferences();
        }
    }
    
    // Delete a journal entry
    public function delete() {
        // First delete all references
        $query = "DELETE FROM " . $this->references_table . " WHERE journal_id = :journal_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':journal_id', $this->id);
        $stmt->execute();
        
        // Then delete the journal entry
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }
    
    // Get mood statistics for a user
    public function getMoodStatistics($user_id, $start_date = null, $end_date = null) {
        // Create base query
        $query = "SELECT mood, COUNT(*) as count 
                  FROM " . $this->table . " 
                  WHERE user_id = :user_id";
        
        // Add date filters if specified
        if($start_date) {
            $query .= " AND entry_date >= :start_date";
        }
        
        if($end_date) {
            $query .= " AND entry_date <= :end_date";
        }
        
        // Group by mood
        $query .= " GROUP BY mood";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        
        if($start_date) {
            $stmt->bindParam(':start_date', $start_date);
        }
        
        if($end_date) {
            $stmt->bindParam(':end_date', $end_date);
        }
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $mood_stats = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mood_stats[$row['mood']] = $row['count'];
        }
        
        return $mood_stats;
    }
}