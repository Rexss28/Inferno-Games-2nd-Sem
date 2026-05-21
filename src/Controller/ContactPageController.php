<?php

namespace App\Controller;

use App\Form\ContactMessageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class ContactPageController extends AbstractController
{
    /** @var array<string, string> */
    private const SUBJECT_LABELS = [
        'general' => 'General question',
        'orders' => 'Store & orders',
        'technical' => 'Technical / client help',
        'partners' => 'Partnerships & press',
        'coursework' => 'Platform Technology — course / grading',
    ];

    #[Route('/contact/page', name: 'app_contact_page', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM_EMAIL)%')]
        string $mailerFromEmail,
        #[Autowire('%env(CONTACT_INBOX_EMAIL)%')]
        string $contactInboxEmail,
    ): Response {
        $form = $this->createForm(ContactMessageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{name: string, email: string, subject: string, message: string} $data */
            $data = $form->getData();
            $topicKey = $data['subject'];
            $topicLabel = self::SUBJECT_LABELS[$topicKey] ?? $topicKey;

            $body = sprintf(
                "New message from the Inferno Games contact form.\n\n".
                "Name: %s\n".
                "Email: %s\n".
                "Topic: %s\n\n".
                "Message:\n%s\n",
                $data['name'],
                $data['email'],
                $topicLabel,
                $data['message']
            );

            $email = (new Email())
                ->from(new Address($mailerFromEmail, 'Inferno Games'))
                ->to(new Address($contactInboxEmail))
                ->replyTo(new Address($data['email'], $data['name']))
                ->subject(sprintf('[Inferno Games] Contact — %s (%s)', $topicLabel, $data['name']))
                ->text($body);

            try {
                $mailer->send($email);
            } catch (TransportExceptionInterface) {
                $this->addFlash('error', 'We could not send your message right now. Please try again in a few minutes.');

                return $this->redirectToRoute('app_contact_page', [], Response::HTTP_SEE_OTHER);
            }

            $this->addFlash('success', 'Thanks — your message was sent. We will reply to '.$data['email'].' when we can.');

            return $this->redirectToRoute('app_contact_page', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contact_page/index.html.twig', [
            'form' => $form,
        ]);
    }
}
