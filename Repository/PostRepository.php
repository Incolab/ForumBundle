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

    const SQL_FIND = "SELECT p.id AS p_id, p.topic_id AS p_topic_id, p.author_id AS p_author_id, p.message AS p_message, "
            . "p.createdat AS p_created_at, p.updatedat AS p_updated_at, "
            . "u.id AS u_id, u.username AS u_username, u.avatar AS u_avatar, "
            . "t.id AS t_id, t.subject AS t_subject, t.slug AS t_slug, t.num_posts AS t_num_posts, "
            . "t.created_at AS t_created_at, t.is_pinned AS t_is_pinned, t.is_buried AS t_is_buried "
            . "FROM forum_post p "
            . "LEFT JOIN user_account u ON p.author_id = u.id "
            . "LEFT JOIN forum_topic t ON p.topic_id = t.id "
            . "LEFT JOIN forum_category c ON t.category_id = c.id "
            . "LEFT JOIN forum_category pc ON c.parent_id = pc.id "
            . "%s";
    const SQL_FIND_COMPLETE = "SELECT p.id AS p_id, p.topic_id AS p_topic_id, p.author_id AS p_author_id, p.message AS p_message, "
            . "p.createdat AS p_created_at, p.updatedat AS p_updated_at, "
            . "u.id AS u_id, u.username AS u_username, u.avatar AS u_avatar, "
            . "t.id AS t_id, t.subject AS t_subject, t.slug AS t_slug, t.num_posts AS t_num_posts, "
            . "t.created_at AS t_created_at, t.is_pinned AS t_is_pinned, t.is_buried AS t_is_buried, "
            . "c.id AS c_id, c.parent_id AS c_parent_id, c.last_topic_id AS c_last_topic_id, c.last_post_id AS c_last_post_id, "
            . "c.name AS c_name, c.description AS c_description, c.slug AS c_slug, c.position AS c_position, c.num_topics AS c_num_topics, "
            . "c.num_posts AS c_num_posts,"
            . "pc.id AS pc_id, pc.parent_id AS pc_parent_id, pc.last_topic_id AS pc_last_topic_id, pc.last_post_id AS pc_last_post_id, "
            . "pc.name AS pc_name, pc.description AS pc_description, pc.slug AS pc_slug, pc.position AS pc_position, pc.num_topics AS pc_num_topics, "
            . "pc.num_posts AS pc_num_posts "
            . "FROM forum_post p "
            . "LEFT JOIN user_account u ON p.author_id = u.id "
            . "LEFT JOIN forum_topic t ON p.topic_id = t.id "
            . "LEFT JOIN forum_category c ON t.category_id = c.id "
            . "LEFT JOIN forum_category pc ON c.parent_id = pc.id "
            . "%s";
    const SQL_INSERT = "INSERT INTO forum_post (id, topic_id, author_id, message, createdat, updatedat) "
            . "VALUES (nextval('forum_post_id_seq'),?,?,?,?,?)";
    const SQL_UPDATE = "UPDATE forum_post SET topic_id = ?, author_id = ?, message = ?, createdat = ?, updatedat = ? "
            . "WHERE id = ?";
    const SQL_UNTILIDBYTOPICSLUG = "SELECT COUNT(p.id) "
            . "FROM forum_post p "
            . "LEFT JOIN forum_topic t ON p.topic_id = t.id "
            . "WHERE t.slug = ? AND p.id < ?";

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
        $sql = sprintf(self::SQL_FIND, "WHERE p.id = ?");
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $postId, \PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();
        $stmt->closeCursor();
        if (!$res) {
            return null;
        }
        $post = self::hydratePost($res, "p");
        $post->setAuthor(UserRepository::lightHydrateUser($res, "u"));
        $topicRepository = new TopicRepository($this->dbal);
        $topic = $topicRepository->getTopic($slugTopic, $slugCat, $slugParentCat);
        $post->setTopic($topic);

        return $post;
    }

    public function getNbPostsByTopic(Topic $topic) {
        $sql = "SELECT COUNT(p.id) FROM forum_post p WHERE p.topic_id = ?";
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $topic->getId(), \PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();

        return (int) $res["count"];
    }

    public function getNbPostsUntilIdByTopicSlug($topicSlug, $postid) {
        $stmt = $this->dbal->prepare(self::SQL_UNTILIDBYTOPICSLUG);
        $stmt->bindValue(1, $topicSlug, \PDO::PARAM_STR);
        $stmt->bindValue(2, $postid, \PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetch();

        return (int) $res["count"];
    }

    public function findLasts(int $limit) {
        $sql = sprintf(self::SQL_FIND_COMPLETE, "ORDER BY p.id DESC LIMIT ?");
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $posts = [];
        while ($res = $stmt->fetch()) {
            $post = self::hydratePost($res, "p");
            $post->setAuthor(UserRepository::lightHydrateUser($res, "u"));
            $topic = TopicRepository::hydrateTopic($res, "t");
            $cat = CategoryRepository::hydrateCategory($res, "c");
            $pCat = CategoryRepository::hydrateCategory($res, "pc");
            $cat->setParent($pCat);
            $topic->setCategory($cat);
            $post->setTopic($topic);
            $posts[] = $post;
        }
        $stmt->closeCursor();

        return $posts;
    }

    public function findLastOfTopic(Topic $topic) {
        $sql = sprintf(self::SQL_FIND, "WHERE t.id = ? AND NOT p.id = ? ORDER BY p.id DESC LIMIT 1");
        $stmt = $this->dbal->prepare($sql);
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

    public function findLastOfCategory(Category $category) {
        $sql = sprintf(self::SQL_FIND, "WHERE c.id = ? ORDER BY p.id DESC LIMIT 1");
        $stmt = $this->dbal->prepare($sql);
        $stmt->bindValue(1, $category->getId(), \PDO::PARAM_INT);
        $stmt->execute();

        $res = $stmt->fetch();
        $stmt->closeCursor();

        if (!$res) {
            return null;
        }

        return self::hydratePost($res, "p");
    }

    public function findLastOfParentCategory(Category $category) {
        $sql = sprintf(self::SQL_FIND, "where pc.id = ? order by pc.id DESC LIMIT 1");
        $stmt = $this->dbal->prepare($sql);
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

    public function create_database() {
        // will be moved
        $shemaCreate = new \Incolab\ForumBundle\Resources\SchemaDatabase\CreateShema($this->dbal);
        return $shemaCreate->create_database();
    }

}
