<?php

namespace Incolab\ForumBundle\Repository;

use Incolab\DBALBundle\Manager\Manager;
use Incolab\ForumBundle\Entity\Post;
use Incolab\ForumBundle\Entity\Topic;
use Incolab\ForumBundle\Entity\Category;
use UserBundle\Repository\UserRepository;
use Incolab\ForumBundle\Repository\TopicRepository;

/**
 * PostRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PostRepository extends Manager {
    /*
     * id        | integer                        | non NULL
     * topic_id  | integer                        |
     * author_id | integer                        |
     * message   | text                           | non NULL
     * createdat | timestamp(0) without time zone | non NULL
     * updatedat | timestamp(0) without time zone | Par défaut, NULL::timestamp without time zone
     * Index :
     * "forum_post_pkey" PRIMARY KEY, btree (id)
     * "idx_996bcc5a1f55203d" btree (topic_id)
     * "idx_996bcc5af675f31b" btree (author_id)
     * Contraintes de clés étrangères :
     * "fk_996bcc5a1f55203d" FOREIGN KEY (topic_id) REFERENCES forum_topic(id) ON DELETE CASCADE
     * "fk_996bcc5af675f31b" FOREIGN KEY (author_id) REFERENCES fos_user(id)
     * Référencé par :
     * TABLE "forum_category" CONSTRAINT "fk_21bf94262d053f64" FOREIGN KEY (last_post_id) REFERENCES forum_post(id) ON DELETE SET NULL
     * TABLE "forum_topic" CONSTRAINT "fk_853478cc2d053f64" FOREIGN KEY (last_post_id) REFERENCES forum_post(id) ON DELETE SET NULL
     * TABLE "forum_topic" CONSTRAINT "fk_853478cc58056fd0" FOREIGN KEY (first_post_id) REFERENCES forum_post(id)
     */

    const SQL_GETPOST = "SELECT p.id AS p_id, p.topic_id AS p_topic_id, p.author_id AS p_author_id, p.message AS p_message, "
            . "p.createdat AS p_created_at, p.updatedat AS p_updated_at, "
            . "a.id AS a_id, a.username AS a_username, a.avatar AS a_avatar "
            . "FROM forum_post p "
            . "LEFT JOIN fos_user a ON p.author_id = a.id "
            . "WHERE p.id = ?";
    const SQL_GETLASTOFTOPIC = "SELECT p.id AS p_id, p.topic_id AS p_topic_id, p.author_id AS p_author_id, p.message AS p_message, "
            . "p.createdat AS p_created_at, p.updatedat AS p_updated_at "
            . "FROM forum_post p "
            . "LEFT JOIN forum_topic t ON p.topic_id = t.id "
            . "WHERE t.id = ? AND NOT p.id = ? "
            . "ORDER BY p.id DESC LIMIT 1";
    const SQL_GETLASTOFCATEGORY = "SELECT p.id AS p_id, p.topic_id AS p_topic_id, p.author_id AS p_author_id, p.message AS p_message, "
            . "p.createdat AS p_created_at, p.updatedat AS p_updated_at "
            . "FROM forum_post p "
            . "LEFT JOIN forum_topic t ON p.topic_id = t.id "
            . "LEFT JOIN forum_category c ON t.category_id = c.id "
            . "WHERE c.id = ? "
            . "ORDER BY p.id DESC LIMIT 1";
    const SQL_NBPOSTSBYTOPIC = "SELECT COUNT(p.id) FROM forum_post p WHERE p.topic_id = ?";
    const SQL_INSERT = "INSERT INTO forum_post (id, topic_id, author_id, message, createdat, updatedat) "
            . "VALUES (nextval('forum_post_id_seq'),?,?,?,?,?)";
    const SQL_UPDATE = "UPDATE forum_post SET topic_id = ?, author_id = ?, message = ?, createdat = ?, updatedat = ? "
            . "WHERE id = ?";
    const SQL_UNTILIDBYTOPICSLUG = "SELECT COUNT(p.id) "
            . "FROM forum_post p "
            . "LEFT JOIN forum_topic t ON p.topic_id = t.id "
            . "WHERE t.slug = ? AND p.id < ?";

    public static $strGetNbPostsUntilId = "SELECT COUNT(p.id) "
            . "FROM IncolabForumBundle:Post p "
            . "WHERE p.topic = :topic AND p.id < :postid";
    

    public static function hydratePost($data = [], $key = "") {
        $post = new Post();
        $post->setId($data[$key . "_id"])
                ->setMessage($data[$key . "_message"])
                ->setCreatedAt(\DateTime::createFromFormat("Y-m-d H:i:s", $data[$key . "_created_at"]));

        if (isset($data[$key . "_updated_at"]) && $data[$key . "_updated_at"] !== null) {
            $post->setUpdatedAt(\DateTime::createFromFormat("Y-m-d H:i:s", $data[$key . "_updated_at"]));
        }

        return $post;
    }

    public function getPost($slugParentCat, $slugCat, $slugTopic, $postId) {
        $stmt = $this->dbal->prepare(self::SQL_GETPOST);
        $stmt->bindValue(1, $postId, \PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();
        $stmt->closeCursor();
        if (!$res) {
            return null;
        }
        $post = self::hydratePost($res, "p");
        $post->setAuthor(UserRepository::lightHydrateUser($res, "a"));
        $topicRepository = new TopicRepository($this->dbal);
        $topic = $topicRepository->getTopic($slugTopic, $slugCat, $slugParentCat);
        $post->setTopic($topic);
        
        return $post;
    }

    public function getNbPostsByTopic(Topic $topic) {
        $stmt = $this->dbal->prepare(self::SQL_NBPOSTSBYTOPIC);
        $stmt->bindValue(1, $topic->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();

        return (int) $res["count"];
    }

    public function getNbPostsUntilId(Topic $topic, $postid) {
        $params = [":topic" => $topic, ":postid" => $postid];
        $query = $this->_em->createQuery(self::$strGetNbPostsUntilId)
                ->setParameters($params);
        return $query->getSingleScalarResult();
    }

    public function getNbPostsUntilIdByTopicSlug($topicSlug, $postid) {
        $stmt = $this->dbal->prepare(self::SQL_UNTILIDBYTOPICSLUG);
        $stmt->bindValue(1, $topicSlug, \PDO::PARAM_STR);
        $stmt->bindValue(2, $postid, \PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();

        return (int) $res["count"];
    }
    
    public function findLastOfTopic(Topic $topic)
    {
        $stmt = $this->dbal->prepare(self::SQL_GETLASTOFTOPIC);
        $stmt->bindValue(1, $topic->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $topic->getFirstPost()->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        
        $res = $stmt->fetch();
        $stmt->closeCursor();
        
        if (!$res) {
            return null;
        }
        
        return self::hydratePost($res, "p");
    }
    
    public function findLastOfCategory(Category $category)
    {
        $stmt = $this->dbal->prepare(self::SQL_GETLASTOFCATEGORY);
        $stmt->bindValue(1, $category->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        
        $res = $stmt->fetch();
        $stmt->closeCursor();
        
        if (!$res) {
            return null;
        }
        
        return self::hydratePost($res, "p");
    }

    public function persist(Post $post) {
        if ($post->getId() === null) {
            return $this->insert($post);
        }
        return $this->update($post);
    }

    private function insert(Post $post) {
        $stmt = $this->dbal->prepare(self::SQL_INSERT);
        
        $stmt->bindValue(1, $post->getTopic()->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $post->getAuthor()->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(3, $post->getMessage(), \PDO::PARAM_STR);
        $stmt->bindValue(4, $post->getCreatedAt()->format("Y-m-d H:i:s"), \PDO::PARAM_STR);
        $updatedat = null;
        if ($post->getUpdatedAt()) {
            $updatedat = $post->getUpdatedAt()->format("Y-m-d H:i:s");
        }
        $stmt->bindValue(5, $updatedat, \PDO::PARAM_STR);

        $stmt->execute();
        $stmt->closeCursor();
        $post->setId($this->dbal->lastInsertId("forum_post_id_seq"));

        return $post;
    }

    private function update(Post $post) {
        $stmt = $this->dbal->prepare(self::SQL_UPDATE);
        
        $stmt->bindValue(1, $post->getTopic()->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(2, $post->getAuthor()->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(3, $post->getMessage(), \PDO::PARAM_STR);
        $stmt->bindValue(4, $post->getCreatedAt()->format("Y-m-d H:i:s"), \PDO::PARAM_STR);
        $updatedat = null;
        if ($post->getUpdatedAt()) {
            $updatedat = $post->getUpdatedAt()->format("Y-m-d H:i:s");
        }
        $stmt->bindValue(5, $updatedat, \PDO::PARAM_STR);
        $stmt->bindValue(6, $post->getId(), \PDO::PARAM_INT);
        $stmt->execute();

        $stmt->closeCursor();

        return $post;
    }

    public function remove(Post $post) {
        $sql = "DELETE FROM forum_post WHERE id = ?";
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $post->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
    }

}
