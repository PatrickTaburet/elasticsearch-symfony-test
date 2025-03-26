<?php

namespace App\Controller;

use App\Form\Type\SearchFormType;
use Elastica\Query\BoolQuery;
use Elastica\Query\MatchPhrase;
use Elastica\Query\MatchQuery;
use Elastica\Query\Range as QueryRange;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly PaginatedFinderInterface $finder
    )
    {
        
    }
    #[Route('/search', name: 'app_search')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            /** @var SearchModel $data */
            $data = $form->getData();
            $page = $request->query->getInt('page', 1);

            $boolQuery = new BoolQuery();
            if ($data->query){
                $boolQuery->addMust(new MatchPhrase('title', $data->query));
            }
            if ($data->category){
                $boolQuery->addFilter(new MatchQuery('category.id', $data->category->getId()));
            }
            if ($data->createdThisMonth){
                $range = new QueryRange('createdAt', [
                    'gte' =>(new \DateTimeImmutable('-1 month'))->format('Y-m-d'),
                ]);
                $boolQuery->addFilter($range);
            }
            $result = $this->finder->createPaginatorAdapter($boolQuery);
            $pagination = $this->paginator->paginate($result, $page);
        }

        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'pagination' => $pagination ?? []
        ]);
    }
}
