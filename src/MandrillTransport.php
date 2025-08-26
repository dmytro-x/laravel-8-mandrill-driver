<?php

namespace LaravelMandrill;

use GuzzleHttp\ClientInterface;
use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Swift_Mime_SimpleMessage;
use Swift_Transport;
use Swift_Events_SendEvent;

class MandrillTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The Mandrill API key.
     *
     * @var string
     */
    protected $key;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var array
     */
    public $mandrillFullResponse;

    /**
     * @var bool
     */
    protected $sendCcAndBccInOneEmail = false;

    /**
     * Create a new Mandrill transport instance.
     *
     * @param \GuzzleHttp\ClientInterface $client
     * @param string $key
     * @param array $headers
     */
    public function __construct(ClientInterface $client, string $key, array $headers = [])
    {
        $this->key = $key;
        $this->setHeaders($headers);
        $this->client = $client;
    }

    public function sendCcAndBccInOneEmail()
    {
        $this->sendCcAndBccInOneEmail = true;
        return $this;
    }

    /**
     * Iterate through registered plugins and execute plugins' methods.
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @return void
     */
    protected function beforeSendPerformed(Swift_Mime_SimpleMessage $message)
    {
        $event = new Swift_Events_SendEvent($this, $message);

        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, 'beforeSendPerformed')) {
                $plugin->beforeSendPerformed($event);
            }
        }

        foreach ($this->getHeaders() as $key => $value) {
            $message->getHeaders()->addTextHeader(
                $key, $value,
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->mandrillFullResponse = $this->client->request('POST', 'https://mandrillapp.com/api/1.0/messages/send-raw.json', [
            'form_params' => [
                'key' => $this->key,
                'raw_message' => $message->toString(),
                'async' => true,
            ],
        ]);

        $message->getHeaders()->addTextHeader(
            'X-Message-ID', $this->getMessageId($this->mandrillFullResponse)
        );

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * Get the message ID from the response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return string
     * @throws \JsonException
     */
    protected function getMessageId(ResponseInterface $response)
    {
        $response = json_decode((string) $response->getBody(), true);

        return Arr::get($response, '0._id');
    }

    /**
     * Get the API key being used by the transport.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the API key being used by the transport.
     *
     * @param  string  $key
     * @return string
     */
    public function setKey($key)
    {
        return $this->key = $key;
    }

    /**
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers ?? [];
    }

    /**
     * Add custom header, check docs for available options at:
     * https://mailchimp.com/developer/transactional/docs/smtp-integration/#customize-messages-with-smtp-headers
     *
     * @param array|null $headers
     */
    public function setHeaders(array $headers = null)
    {
        $this->headers = $headers ?? [];
    }

    /**
     *
     * @return array
     */
    public function getResponseBody()
    {
        return json_decode((string) $this->mandrillFullResponse->getBody(), true);
    }

    /**
     *
     * @return string
     */
    public function getFullResponse()
    {
        return $this->mandrillFullResponse;
    }
}
