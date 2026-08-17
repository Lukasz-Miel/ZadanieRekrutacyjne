<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Person;
use App\Entity\WorkExperience;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApplicationController extends AbstractController
{
    #[Route('/api/application', name: 'api_application', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'success' => false,
                'errors' => ['Nieprawidłowe dane JSON.']
            ], Response::HTTP_BAD_REQUEST);
        }

        $person = new Person();

        $person->setFirstName(trim($data['firstName'] ?? ''));
        $person->setLastName(trim($data['lastName'] ?? ''));

        try {
            $birthDate = new \DateTime(
                $data['birthDate'] ?? ''
            );

            $person->setBirthDate($birthDate);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'errors' => [
                    'birthDate' => 'Nieprawidłowa data urodzenia.'
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        $contact = new Contact();

        $contact->setPhone(
            trim($data['phone'] ?? '')
        );

        $contact->setEmail(
            trim($data['email'] ?? '')
        );

        $person->setContact($contact);

        $experiences = $data['workExperiences'] ?? [];

        if (!is_array($experiences)) {
            return $this->json([
                'success' => false,
                'errors' => [
                    'workExperiences' => 'Nieprawidłowe dane doświadczenia.'
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        foreach ($experiences as $experienceData) {

            $experience = new WorkExperience();

            $experience->setCompany(
                trim($experienceData['company'] ?? '')
            );

            $experience->setPosition(
                trim($experienceData['position'] ?? '')
            );

            try {
                $dateFrom = new \DateTime(
                    $experienceData['dateFrom'] ?? ''
                );

                $dateTo = new \DateTime(
                    $experienceData['dateTo'] ?? ''
                );

                $experience->setDateFrom($dateFrom);
                $experience->setDateTo($dateTo);

            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'errors' => [
                        'workExperiences' =>
                            'Nieprawidłowa data doświadczenia.'
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }

            $person->addWorkExperience($experience);
        }

        $errors = [];

        $personErrors = $validator->validate($person);

        foreach ($personErrors as $error) {
            $errors[] = $error->getMessage();
        }

        $contactErrors = $validator->validate($contact);

        foreach ($contactErrors as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($person->getWorkExperiences() as $experience) {

            $experienceErrors = $validator->validate($experience);

            foreach ($experienceErrors as $error) {
                $errors[] = $error->getMessage();
            }
        }

        if (count($errors) > 0) {
            return $this->json([
                'success' => false,
                'errors' => $errors
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->persist($person);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Dane zostały zapisane.',
            'data' => [
                'id' => $person->getId(),

                'firstName' => $person->getFirstName(),
                'lastName' => $person->getLastName(),
                'birthDate' =>
                    $person->getBirthDate()->format('Y-m-d'),

                'phone' => $contact->getPhone(),
                'email' => $contact->getEmail(),

                'workExperiences' =>
                    array_map(
                        static function (WorkExperience $experience) {
                            return [
                                'company' =>
                                    $experience->getCompany(),

                                'position' =>
                                    $experience->getPosition(),

                                'dateFrom' =>
                                    $experience->getDateFrom()
                                        ->format('Y-m-d'),

                                'dateTo' =>
                                    $experience->getDateTo()
                                        ->format('Y-m-d'),
                            ];
                        },
                        $person->getWorkExperiences()->toArray()
                    )
            ]
        ], Response::HTTP_CREATED);
    }
}