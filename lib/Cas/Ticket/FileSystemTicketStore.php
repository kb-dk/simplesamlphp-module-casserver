<?php

class sspmod_sbcasserver_Cas_Ticket_FileSystemTicketStore extends sspmod_sbcasserver_Cas_Ticket_TicketStore
{

    private $pathToTicketDirectory;

    public function __construct($config)
    {
        $storeConfig = $config->getValue('ticketstore', array('directory' => 'ticketcache'));

        if (!is_string($storeConfig['directory'])) {
            throw new Exception('Invalid directory option in config.');
        }

        $path = $config->resolvePath($storeConfig['directory']);

        if (!is_dir($path))
            throw new Exception('Directory for CAS Server ticket storage [' . $path . '] does not exists. ');

        if (!is_writable($path))
            throw new Exception('Directory for CAS Server ticket storage [' . $path . '] is not writable. ');

        $this->pathToTicketDirectory = preg_replace('/\/$/', '', $path);
    }

    public function getTicket($ticketIdId)
    {
        $filename = $this->pathToTicketDirectory . '/' . $ticketIdId;

        if (!file_exists($filename))
            throw new Exception('Could not find ticket');

        $content = file_get_contents($filename);

        return unserialize($content);
    }

    public function addTicket($ticket)
    {
        $filename = $this->pathToTicketDirectory . '/' . $ticket['id'];
        file_put_contents($filename, serialize($ticket));
    }

    public function removeTicket($ticketId)
    {
        $filename = $this->pathToTicketDirectory . '/' . $ticketId;

        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}

?>