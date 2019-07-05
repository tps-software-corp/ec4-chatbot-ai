<?php

namespace Plugin\TPSChatbotAI\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_tpschatbotai_config")
 * @ORM\Entity(repositoryClass="Plugin\TPSChatbotAI\Repository\ConfigRepository")
 */
class Config
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="uid", type="string")
     */
    private $uid;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of uid
     */ 
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set the value of uid
     *
     * @return  self
     */ 
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }
}
