<?php
namespace Neos\RedirectHandler\DatabaseStorage\Domain\Model;

/*
 * This file is part of the Neos.RedirectHandler.DatabaseStorage package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Neos\RedirectHandler\RedirectInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Now;

/**
 * A Redirect model that represents a HTTP redirect
 *
 * @see RedirectService
 *
 * @Flow\Entity
 * @ORM\Table(
 *    indexes={
 * 		@ORM\Index(name="sourceuripathhash",columns={"sourceuripathhash","host"}),
 * 		@ORM\Index(name="targeturipathhash",columns={"targeturipathhash","host"})
 *    }
 * )
 */
class Redirect implements RedirectInterface
{
    /**
     * @var DateTime
     */
    protected $creationDateTime;

    /**
     * @var DateTime
     */
    protected $lastModificationDateTime;

    /**
     * Auto-incrementing version of this node data, used for optimistic locking
     *
     * @ORM\Version
     * @var integer
     */
    protected $version;

    /**
     * Relative URI path for which this redirect should be triggered
     *
     * @var string
     * @ORM\Column(length=4000)
     */
    protected $sourceUriPath;

    /**
     * MD5 hash of the Source Uri Path
     *
     * @var string
     * @ORM\Column(length=32)
     * @Flow\Identity
     */
    protected $sourceUriPathHash;

    /**
     * Target URI path to which a redirect should be pointed
     *
     * @var string
     * @ORM\Column(length=500)
     */
    protected $targetUriPath;

    /**
     * MD5 hash of the Target Uri Path
     *
     * @var string
     * @ORM\Column(length=32)
     */
    protected $targetUriPathHash;

    /**
     * Status code to be send with the redirect header
     *
     * @var integer
     * @Flow\Validate(type="NumberRange", options={ "minimum"=100, "maximum"=599 })
     */
    protected $statusCode;

    /**
     * Full qualified host name
     *
     * @var string
     * @ORM\Column(nullable=true)
     * @Flow\Identity
     */
    protected $host;

    /**
     * @var integer
     */
    protected $hitCounter;

    /**
     * @var DateTime
     * @ORM\Column(nullable=true)
     */
    protected $lastHit;

    /**
     * Human readable name of the creator
     *
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $creator;

    /**
     * Human readable description
     *
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $comment;

    /**
     * @ORM\Column(options={"default": "generated"})
     * @var string
     */
    protected $type;

    /**
     * @var DateTime
     * @ORM\Column(nullable=true)
     */
    protected $startDateTime;

    /**
     * @var DateTime
     * @ORM\Column(nullable=true)
     */
    protected $endDateTime;

    /**
     * @param string $sourceUriPath relative URI path for which a redirect should be triggered
     * @param string $targetUriPath target URI path to which a redirect should be pointed
     * @param integer $statusCode status code to be send with the redirect header
     * @param string $host Full qualified host name
     * @param string $creator human readable name of the creator
     * @param string $comment textual description of the redirect
     * @param string $type on of the constants in th Redirect class
     * @param DateTime|null $startDateTime when the redirect is valid
     * @param DateTime|null $endDateTime when the redirect has expired
     * @throws \Exception
     */
    public function __construct(
        $sourceUriPath,
        $targetUriPath,
        $statusCode,
        $host = null,
        $creator = null,
        $comment = null,
        $type = null,
        DateTime $startDateTime = null,
        DateTime $endDateTime = null
    ) {
        $this->sourceUriPath = trim($sourceUriPath, '/');
        $this->sourceUriPathHash = md5($this->sourceUriPath);
        $this->setTargetUriPath($targetUriPath);
        $this->statusCode = (integer)$statusCode;
        $this->host = $host ? trim($host) : null;
        $this->creator = $creator;
        $this->comment = $comment;
        $this->type = in_array($type,
                [self::REDIRECT_TYPE_GENERATED, self::REDIRECT_TYPE_MANUAL]) ? $type : self::REDIRECT_TYPE_GENERATED;
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;

        $this->hitCounter = 0;

        $this->creationDateTime = new Now();
        $this->lastModificationDateTime = new Now();
    }

    /**
     * @param string $targetUriPath
     * @param integer $statusCode
     * @return void
     * @throws \Exception
     */
    public function update($targetUriPath, $statusCode): void
    {
        $this->setTargetUriPath($targetUriPath);
        $this->statusCode = $statusCode;

        $this->lastModificationDateTime = new Now();
    }

    /**
     * @return DateTime
     */
    public function getCreationDateTime(): DateTime
    {
        return $this->creationDateTime;
    }

    /**
     * @return DateTime
     */
    public function getLastModificationDateTime(): DateTime
    {
        return $this->lastModificationDateTime;
    }

    /**
     * @return string
     */
    public function getSourceUriPath(): string
    {
        return $this->sourceUriPath;
    }

    /**
     * @return string
     */
    public function getSourceUriPathHash(): string
    {
        return $this->sourceUriPathHash;
    }

    /**
     * @param string $targetUriPath
     * @return void
     * @throws \Exception
     */
    public function setTargetUriPath($targetUriPath): void
    {
        $this->targetUriPath = trim($targetUriPath, '/');
        $this->targetUriPathHash = md5($this->targetUriPath);

        $this->lastModificationDateTime = new Now();
    }

    /**
     * @return string
     */
    public function getTargetUriPath(): string
    {
        return $this->targetUriPath;
    }

    /**
     * @return string
     */
    public function getTargetUriPathHash(): string
    {
        return $this->targetUriPathHash;
    }

    /**
     * @param integer $statusCode
     * @return void
     * @throws \Exception
     */
    public function setStatusCode($statusCode): void
    {
        $this->statusCode = $statusCode;

        $this->lastModificationDateTime = new Now();
    }

    /**
     * @return integer
     */
    public function getStatusCode(): int
    {
        return (integer)$this->statusCode;
    }

    /**
     * @return string|null
     */
    public function getHost(): ?string
    {
        return trim($this->host) === '' ? null : $this->host;
    }

    /**
     * @return integer
     */
    public function getHitCounter(): int
    {
        return $this->hitCounter;
    }

    /**
     * @return DateTime|null
     */
    public function getLastHit(): ?DateTime
    {
        return $this->lastHit;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function incrementHitCounter(): void
    {
        $this->hitCounter++;

        $this->lastHit = new Now();
    }

    /**
     * @return string|null
     */
    public function getCreator(): ?string
    {
        return $this->creator;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return DateTime|null
     */
    public function getStartDateTime(): ?DateTime
    {
        return $this->startDateTime;
    }

    /**
     * @return DateTime|null
     */
    public function getEndDateTime(): ?DateTime
    {
        return $this->endDateTime;
    }
}
