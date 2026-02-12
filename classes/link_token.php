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

    private static function hash_token(string $token): string {
        return hash('sha256', $token);
    }

    public static function create(int $quizid): self {
        global $DB;
        /** @var moodle_database $DB */

        $rawtoken = self::generate_token(); // 64 chars
        $hashedtoken = self::hash_token($rawtoken);

        $record = (object) [
            'quizid' => $quizid,
            'tokenhash' => $hashedtoken,
            'timecreated' => time()
        ];
        $id = $DB->insert_record('local_publictestlink_linktoken', $record);

        return new self(
            $id, $record->quizid, $rawtoken, $record->timecreated,
        );
    }

    public static function delete(int $quizid) {
        global $DB;
        /** @var moodle_database $DB */
        $DB->delete_records('local_publictestlink_linktoken', ['quizid' => $quizid], IGNORE_MISSING);
    }

    public static function from_token(string $rawtoken): ?self {
        global $DB;
        $record = $DB->get_record(
            'local_publictestlink_session',
            [
                'token' => self::hash_token($rawtoken),
                'isrevoked' => 0
            ],
            "*",
            IGNORE_MISSING
        );

        if (!$record) return null;

        return new self(
            $record->id, $record->quizid, $rawtoken, $record->timecreated
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