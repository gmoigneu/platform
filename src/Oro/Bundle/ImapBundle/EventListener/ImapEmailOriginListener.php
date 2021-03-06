<?php

namespace Oro\Bundle\ImapBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManager;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class ImapEmailOriginListener
{
    /**
     * @var Mcrypt
     */
    protected $mcrypt;

    /**
     * @var ImapConnectorFactory
     */
    protected $connectorFactory;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @param Mcrypt $mcrypt
     * @param ImapConnectorFactory $connectorFactory
     * @param Registry $doctrine
     */
    public function __construct(Mcrypt $mcrypt, ImapConnectorFactory $connectorFactory, Registry $doctrine)
    {
        $this->mcrypt = $mcrypt;
        $this->connectorFactory = $connectorFactory;
        $this->doctrine = $doctrine;
    }

    /**
     * Create ImapEmailFolder instances for each newly created EmailFolder related to ImapEmailOrigin
     *
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $origin = $event->getObject();
        if ($origin instanceof ImapEmailOrigin && !$origin->getFolders()->isEmpty()) {
            $manager = $this->createManager($origin);
            $folders = $origin->getRootFolders();

            $this->createImapEmailFolders($folders, $manager);
        }
    }

    /**
     * @param ArrayCollection|EmailFolder[] $folders
     * @param ImapEmailFolderManager $manager
     */
    protected function createImapEmailFolders($folders, ImapEmailFolderManager $manager)
    {
        foreach ($folders as $folder) {
            if ($folder->getId() === null) {
                $uidValidity = $manager->getUidValidity($folder);

                if ($uidValidity !== null) {
                    $imapEmailFolder = new ImapEmailFolder();
                    $imapEmailFolder->setUidValidity($uidValidity);
                    $imapEmailFolder->setFolder($folder);

                    $this->doctrine->getManager()->persist($imapEmailFolder);
                }

                if ($folder->hasSubFolders()) {
                    $this->createImapEmailFolders($folder->getSubFolders(), $manager);
                }
            }
        }
    }

    /**
     * @param ImapEmailOrigin $origin
     *
     * @return ImapEmailFolderManager
     */
    protected function createManager(ImapEmailOrigin $origin)
    {
        $config = new ImapConfig(
            $origin->getHost(),
            $origin->getPort(),
            $origin->getSsl(),
            $origin->getUser(),
            $this->mcrypt->decryptData($origin->getPassword())
        );

        $connector = $this->connectorFactory->createImapConnector($config);

        return new ImapEmailFolderManager($connector, $this->doctrine->getManager(), $origin);
    }
}
