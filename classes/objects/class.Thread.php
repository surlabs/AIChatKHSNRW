<?php
declare(strict_types=1);

namespace objects;

use DateTime;
use Exception;
use platform\AIChatDatabase;
use platform\AIChatException;
use objects\Message;

class Thread
{
    private int $id = 0;
    private int $obj_id = 0;
    private string $title;
    private DateTime $created_at;
    private int $user_id = 0;
    private ?string $thread_id = null;

    public function __construct(?int $id = null)
    {
        $this->created_at = new DateTime();
        $this->setTitle();

        if ($id !== null && $id > 0) {
            $this->id = $id;
            $this->loadFromDB();
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getObjId(): int
    {
        return $this->obj_id;
    }

    public function setObjId(int $obj_id): void
    {
        $this->obj_id = $obj_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(?string $title = null): void
    {
        if ($title === null) {
            global $DIC;
            $title = $DIC->language()->txt("rep_robj_xaic_chat_default_title");
        }
        
        $this->title = $title;
    }

    public function setTitleFromMessage(string $message): void
    {
        if (strlen($message) > 100) {
            $message = substr($message, 0, 100) . "...";
        }
        $this->setTitle($message);
    }

    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTime $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getThreadId(): ?string 
    {
        return $this->thread_id;
    }

    public function setThreadId(?string $thread_id): void
    {
        $this->thread_id = $thread_id;
    }

    /**
     * @throws AIChatException
     */
    public function loadFromDB(): void
    {
        $database = new AIChatDatabase();

        $result = $database->select("xaic_threads", ["id" => $this->getId()]);

        if (isset($result[0])) {
            $this->setObjId((int)$result[0]["obj_id"]);
            $this->setTitle($result[0]["title"]);
            $this->setCreatedAt(new DateTime($result[0]["created_at"]));
            $this->setUserId((int)$result[0]["user_id"]);
            $this->setThreadId($result[0]["thread_id"]);
        }
    }

    /**
     * @throws AIChatException
     */
    public function save(): void
    {
        $database = new AIChatDatabase();

        $data = [
            "obj_id" => $this->getObjId(),
            "title" => $this->getTitle(),
            "created_at" => $this->getCreatedAt()->format("Y-m-d H:i:s"),
            "user_id" => $this->getUserId(),
            "thread_id" => $this->getThreadId()
        ];

        if ($this->getId() > 0) {
            $database->update("xaic_threads", $data, ["id" => $this->getId()]);
        } else {
            $id = $database->nextId("xaic_threads");
            $this->setId($id);
            $data["id"] = $id;
            $database->insert("xaic_threads", $data);
        }
    }

    /**
     * @throws AIChatException
     */
    public function delete(): void
    {
        $database = new AIChatDatabase();
        $database->delete("xaic_threads", ["id" => $this->getId()]);
    }

    /**
     * @throws AIChatException
     */
    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "obj_id" => $this->getObjId(),
            "title" => $this->getTitle(),
            "created_at" => $this->getCreatedAt()->format("Y-m-d H:i:s"),
            "user_id" => $this->getUserId(),
            "thread_id" => $this->getThreadId()
        ];
    }

}
