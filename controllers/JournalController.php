
<?php
// controllers/JournalController.php - Journal controller
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Journal.php';
require_once __DIR__ . '/../models/Habit.php';
require_once __DIR__ . '/../models/Goal.php';
require_once __DIR__ . '/../models/Challenge.php';


class JournalController {
    private $conn;
    private $journal;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->journal = new Journal($conn);
    }
    
    // Get all journal entries for a user
    public function getAllJournals($user_id, $limit = null, $offset = 0) {
        return $this->journal->getAllJournals($user_id, $limit, $offset);
    }
    
    // Search journal entries for a user
    public function searchJournals($user_id, $search_term) {
        return $this->journal->searchJournals($user_id, $search_term);
    }
    
    // Get journal entry by ID
    public function getJournalById($id, $user_id) {
        // Get the journal entry
        $this->journal->id = $id;
        
        if($this->journal->getJournalById($id)) {
            // Check if the journal belongs to the user
            if($this->journal->user_id != $user_id) {
                return [
                    'success' => false,
                    'message' => 'Unauthorized access'
                ];
            }
            
            // Format journal data
            return [
                'success' => true,
                'journal' => [
                    'id' => $this->journal->id,
                    'title' => $this->journal->title,
                    'content' => $this->journal->content,
                    'mood' => $this->journal->mood,
                    'entry_date' => $this->journal->entry_date,
                    'created_at' => $this->journal->created_at,
                    'updated_at' => $this->journal->updated_at,
                    'references' => $this->journal->references
                ]
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Journal entry not found'
        ];
    }
    
    // Add a new journal entry
    public function addJournal($journal_data) {
        // Set the journal properties
        $this->journal->user_id = $journal_data['user_id'];
        $this->journal->title = $journal_data['title'];
        $this->journal->content = $journal_data['content'];
        $this->journal->mood = $journal_data['mood'];
        $this->journal->entry_date = $journal_data['entry_date'] ?? date('Y-m-d');
        
        // Set references if any
        if(isset($journal_data['references']) && is_array($journal_data['references'])) {
            $this->journal->references = $journal_data['references'];
        }
        
        // Create the journal entry
        if($this->journal->create()) {
            return [
                'success' => true,
                'message' => 'Journal entry created successfully',
                'journal_id' => $this->journal->id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create journal entry'
            ];
        }
    }
    
    // Update a journal entry
    public function updateJournal($journal_data) {
        // First check if the journal exists and belongs to the user
        $this->journal->id = $journal_data['id'];
        if(!$this->journal->getJournalById($journal_data['id']) || $this->journal->user_id != $journal_data['user_id']) {
            return [
                'success' => false,
                'message' => 'Invalid journal entry or unauthorized access'
            ];
        }
        
        // Set the journal properties
        $this->journal->title = $journal_data['title'];
        $this->journal->content = $journal_data['content'];
        $this->journal->mood = $journal_data['mood'];
        $this->journal->entry_date = $journal_data['entry_date'];
        
        // Set references if any
        if(isset($journal_data['references']) && is_array($journal_data['references'])) {
            $this->journal->references = $journal_data['references'];
        } else {
            $this->journal->references = [];
        }
        
        // Update the journal entry
        if($this->journal->update()) {
            return [
                'success' => true,
                'message' => 'Journal entry updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update journal entry'
            ];
        }
    }
    
    // Delete a journal entry
    public function deleteJournal($id, $user_id) {
        // First check if the journal exists and belongs to the user
        $this->journal->id = $id;
        if(!$this->journal->getJournalById($id) || $this->journal->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Invalid journal entry or unauthorized access'
            ];
        }
        
        // Delete the journal entry
        if($this->journal->delete()) {
            return [
                'success' => true,
                'message' => 'Journal entry deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete journal entry'
            ];
        }
    }
    
    // Get mood statistics for a user
    public function getMoodStatistics($user_id, $start_date = null, $end_date = null) {
        return $this->journal->getMoodStatistics($user_id, $start_date, $end_date);
    }
    
    // Get available references for a user (habits, goals, challenges)
    public function getAvailableReferences($user_id) {
        $references = [
            'habits' => [],
            'goals' => [],
            'challenges' => []
        ];
        
        // Get active habits
        $habit = new Habit($this->conn);
        $habits = $habit->getAllHabits($user_id);
        foreach($habits as $h) {
            $references['habits'][] = [
                'id' => $h['id'],
                'title' => $h['title']
            ];
        }
        
        // Get goals
        $goal = new Goal($this->conn);
        $goals = $goal->getAllGoals($user_id);
        foreach($goals as $g) {
            $references['goals'][] = [
                'id' => $g['id'],
                'title' => $g['title']
            ];
        }
        
        // Get challenges
        $challenge = new Challenge($this->conn);
        $challenges = $challenge->getAllChallenges($user_id);
        foreach($challenges as $c) {
            $references['challenges'][] = [
                'id' => $c['id'],
                'title' => $c['title']
            ];
        }
        
        return $references;
    }
}