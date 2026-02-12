<?php

class publictestlink_link_token {
    public function __construct(
        protected int $id,
        protected int $quizid,
        protected string $token,
        protected int $timecreated
    ) {}

    private static function generate_token(): string {
        return bin2hex(random_bytes(32));
    }

    public static function create(int $quizid): self {
        global $DB;
        /** @var moodle_database $DB */

        $record = (object) [
            'quizid' => $quizid,
            'token' => self::generate_token(),
            'timecreated' => time()
        ];
        $id = $DB->insert_record('local_publictestlink_linktoken', $record);

        return new self(
            $id, $record->quizid, $record->rawtoken, $record->timecreated,
        );
    }

    public static function delete(int $quizid) {
        global $DB;
        /** @var moodle_database $DB */
        $DB->delete_records('local_publictestlink_linktoken', ['quizid' => $quizid], IGNORE_MISSING);
    }

    public static function from_token(string $token): ?self {
        global $DB;
        /** @var moodle_database $DB */
        $record = $DB->get_record('local_publictestlink_linktoken', ['token' => $token], "*", IGNORE_MISSING);

        if (!$record) return null;

        return new self(
            $record->id, $record->quizid, $token, $record->timecreated
        );
    }

    public static function from_quizid(int $quizid) {
        global $DB;
        /** @var moodle_database $DB */
        $record = $DB->get_record('local_publictestlink_linktoken', ['quizid' => $quizid], "*", IGNORE_MISSING);

        if (!$record) return null;

        return new self(
            $record->id, $quizid, $record->token, $record->timecreated
        );
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_quizid(): int {
        return $this->quizid;
    }

    public function get_timecreated(): int {
        return $this->timecreated;
    }
}