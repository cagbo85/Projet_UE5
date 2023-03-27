<?php

namespace App\Controller;

use App\Entity\Personne;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PersonneController extends AbstractController
{

    #[Route('/liste-des-personnes', name: 'listeDesPersonnes')]
    public function listPersonnes(Request $request, UserRepository $repo)
    {
        // on utilise la méthode findAll pour récupérer tous les clients
        // $listePersonnes = $repo->findAll();
        $listePersonnes = $repo->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']);
        $personneConnectee = $this->getUser();

        // on les affiche
        return $this->render('personnes/index.html.twig', [
            'personneConnectee' => $personneConnectee,
            'listePersonnes' => $listePersonnes
        ]);
    }

    #[Route('/rechercher-une-personne', name: 'recherchePage')]
    public function recherchePage(Request $request, UserRepository $repo): Response
    {
        $personneConnectee = $this->getUser();
        $nomRechercher = $request->request->get('nomRechercher');
        $listePersonnes = $repo->createQueryBuilder('p')
            ->where('p.nom like :nom')
            ->setParameter('nom', '%' . $nomRechercher . '%')
            ->getQuery()
            ->getResult();
        return $this->render('admin/recherchePage.html.twig', [
            'personneConnectee' => $personneConnectee,
            'listePersonnes' => $listePersonnes
        ]);
    }
}
