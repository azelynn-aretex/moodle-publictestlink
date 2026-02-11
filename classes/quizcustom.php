<?php

use core\exception\moodle_exception;

class publictestlink_quizcustom {
    public function __construct(
        protected int $id,
        protected string $email,
        protected string $firstname,
        protected string $lastname
    ) {}

    public static function create(string $email, string $firstname, string $lastname): self {
        global $DB;
    
        $email = clean_email($email);

        $firstname = clean_name($firstname);
        $lastname = clean_name($lastname);

        $record = (object) [
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'timecreated' => time()
        ];
        $id = $DB->insert_record('local_publictestlink_shadowuser', $record);

        return new self(
            $id, $record->email, $record->firstname, $record->lastname
        );
    }

    public static function from_id(int $id): ?self {
        global $DB;
        $record = $DB->get_record('local_publictestlink_shadowuser', ['id' => $id], "*", IGNORE_MISSING);
        if (!$record) return null;

        return new self(
            $record->id, $record->email, $record->firstname, $record->lastname
        );
    }

    public static function from_email(string $email): ?self {
        global $DB;
        $email = clean_email($email);

        $record = $DB->get_record('local_publictestlink_shadowuser', ['email' => $email], "*", IGNORE_MISSING);
        if (!$record) return null;

        return new self(
            $record->id, $record->email, $record->firstname, $record->lastname
        );
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_email(): string {
        return $this->email;
    }

    public function get_firstname(): string {
        return $this->firstname;
    }

    public function get_lastname(): string {
        return $this->lastname;
    }

    public function update_names(string $firstname, string $lastname) {
        global $DB;
        /** @var moodle_database $DB */

        $firstname = clean_name($firstname);
        $lastname = clean_name($lastname);

        $DB->update_record('local_publictestlink_shadowuser', [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
        ]);

        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }
}