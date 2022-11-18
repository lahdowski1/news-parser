<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use App\Entity\News;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;


class GenerateNewsCommand extends Command implements MessageHandlerInterface
{
    private $entityManager;
    private $newsRepository;

    public function __construct(EntityManagerInterface $entityManager, NewsRepository $newsRepository, MessageBusInterface $bus)
    {
        $this->entityManager = $entityManager;
        $this->newsRepository = $newsRepository;
        $this->bus = $bus;
       

        parent::__construct();
    }

    public function __invoke(News $newsItem){ //This consumes the news from RabbitMQ
        //Time Complexity: O(1), Space Complexity is O(1)
        echo("Consuming the News from RabbitMQ \n");
        $entityManager = $this->entityManager;
        $news = new News();
        $news->setAuthor($newsItem->getAuthor());
        $news->setTitle($newsItem->getTitle());
        $news->setDescription(substr($newsItem->getDescription(),0,100)); 
        $news->setUrl($newsItem->getUrl());
        $news->setUrlToImage($newsItem->getUrlToImage());
        $news->setPublishedAt($newsItem->getPublishedAt());
        $news->setContent($newsItem->getContent());
        $isPersisted = $this->entityManager->contains($news);
        if(!$isPersisted){ //Checks the database to see if the news has been persisted
            $entityManager->persist($news); //Doctrine uses transactional write-behind
        }
        else{
            $persistedNews = $this->newsRepository->findOneBy([
                'title' => $newsItem['title'],
                'author' => $newsItem['author'],
                'url' => $newsItem['url'],
            ]); //Gets the persisted news from the database
           
            //Only updates the news if the datetime is different
            if($persistedNews->getPublishedAt !== $newsItem->getPublishedAt()){ 
                $persistedNews->setDescription(substr($newsItem->getDescription(),0,100));
                $persistedNews->setUrlToImage($newsItem->getUrlToImage());
                $persistedNews->setPublishedAt($newsItem->getPublishedAt());
                $persistedNews->setContent($newsItem->getContent());
                $entityManager->persist($persistedNews);  //persists the update news
           }
        }
        $entityManager->flush(); 

    }

    protected static $defaultName = 'GenerateNewsCommand';
    protected function configure(): void
    {
        
    }

    protected function execute(InputInterface $input, OutputInterface $output):int
    {
        $io = new SymfonyStyle($input, $output);
       
        $this->publishNewsToRabbitMQ($this->bus);
       //$this->storeNews() ;
      


        $io->success('News generated successfully');

        return Command::SUCCESS;
    }

    public function getNews($urlParams){ //Time Complexity is O(1), Space Complexity is O(1)
        try {
            $client = new Client();
            $apiRequest = $client->request('GET', 'https://newsapi.org/v2/' .$urlParams.'&apiKey=' . '54f60d52a9b94d7a8b870c290fa2dd7e');
            return json_decode($apiRequest->getBody()->getContents(), true);
        } catch (RequestException $e) {
            //For handling exception
            echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                echo Psr7\str($e->getResponse());
            }
        }
    }
  

    public function publishNewsToRabbitMQ(MessageBusInterface $bus){ //This publishes the News to RabbitMQ
        $urlParams = 'top-headlines?sources=cnn';
        $consumedNews = $this->getNews($urlParams) ;
        foreach($consumedNews['articles'] as $newsItem){ //Time Complexity is O(n). Space Complexity is O(n)
            //dd($newsItem);
            $news = new News();
            $news->setAuthor($newsItem['author']);
            $news->setTitle($newsItem['title']);
            $news->setDescription(substr($newsItem['description'],0,100)); 
            $news->setUrl($newsItem['url']);
            $news->setUrlToImage($newsItem['urlToImage']);
            $news->setPublishedAt(new \DateTimeImmutable($newsItem['publishedAt']));
            $news->setContent($newsItem['content']);
            $bus->dispatch($news);
        }
       
        return Command::SUCCESS;
    }
}
