<?php

namespace Penobit\Git;

    class Commit {
        /** @var CommitId */
        private $id;

        /** @var string */
        private $subject;

        /** @var null|string */
        private $body;

        /** @var string */
        private $authorEmail;

        /** @var null|string */
        private $authorName;

        /** @var \DateTimeImmutable */
        private $authorDate;

        /** @var string */
        private $committerEmail;

        /** @var null|string */
        private $committerName;

        /** @var \DateTimeImmutable */
        private $committerDate;

        /**
         * @param string $subject
         * @param null|string $body
         * @param string $authorEmail
         * @param null|string $authorName
         * @param string $committerEmail
         * @param null|string $committerName
         */
        public function __construct(
            CommitId $id,
            $subject,
            $body,
            $authorEmail,
            $authorName,
            \DateTimeImmutable $authorDate,
            $committerEmail,
            $committerName,
            \DateTimeImmutable $committerDate
        ) {
            $this->id = $id;
            $this->subject = $subject;
            $this->body = $body;
            $this->authorEmail = $authorEmail;
            $this->authorName = $authorName;
            $this->authorDate = $authorDate;
            $this->committerEmail = $committerEmail;
            $this->committerName = $committerName;
            $this->committerDate = $committerDate;
        }

        /**
         * @return CommitId
         */
        public function getId() {
            return $this->id;
        }

        /**
         * @return string
         */
        public function getSubject() {
            return $this->subject;
        }

        /**
         * @return null|string
         */
        public function getBody() {
            return $this->body;
        }

        /**
         * @return null|string
         */
        public function getAuthorName() {
            return $this->authorName;
        }

        /**
         * @return string
         */
        public function getAuthorEmail() {
            return $this->authorEmail;
        }

        /**
         * @return \DateTimeImmutable
         */
        public function getAuthorDate() {
            return $this->authorDate;
        }

        /**
         * @return null|string
         */
        public function getCommitterName() {
            return $this->committerName;
        }

        /**
         * @return string
         */
        public function getCommitterEmail() {
            return $this->committerEmail;
        }

        /**
         * @return \DateTimeImmutable
         */
        public function getCommitterDate() {
            return $this->committerDate;
        }

        /**
         * Alias for getAuthorDate().
         *
         * @return \DateTimeImmutable
         */
        public function getDate() {
            return $this->authorDate;
        }
    }
