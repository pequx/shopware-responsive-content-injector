<?php

namespace PhagResponsiveContentInjector\Tests;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use League\Flysystem\File;
use PhagResponsiveContentInjector\Models\FinalVariable;
use PhagResponsiveContentInjector\Models\Repository;
use PhagResponsiveContentInjector\PhagResponsiveContentInjector;
use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Blog\Blog;
use Shopware\Models\Category\Category;
use Shopware\Models\User\User;
use PhagResponsiveContentInjector\Models\PhagResponsiveContentInjector as Model;

/**
 * Class WidgetControllerTest
 * @package PhagResponsiveContentInjector\Tests
 */
class WidgetControllerTest extends \Enlight_Components_Test_Controller_TestCase
{
    use DatabaseTestCaseTrait;

    const DEFAULT_CONFIG_ITEMS_COUNT = 7;
    const DEFAULT_FINAL_VAR_COUNT = 3;

    /**
     * @var ModelManager
     */
    protected $modelManager;

    /**
     * @var Blog
     */
    protected $blog;

    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * @var \DOMNodeList
     */
    protected $nodes;

    /**
     * @var string
     */
    protected $html;

    /**
     * @var \DOMXPath
     */
    protected $xpath;

    /**
     * @var array
     */
    protected $config;

    /**
     * @inheritdoc
     */
    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->modelManager = Shopware()->Models();
    }

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->html = new BlogContentProvider();
        $this->html->init();

        $authors = $this->modelManager->createQueryBuilder()
            ->select('author')
            ->from(User::class, 'author')
            ->where('author.active = 1')
            ->andWhere('author.extendedEditor = 1')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_OBJECT);
        /** @var User $author */
        $author = $authors[rand(0, count($authors)-1)];
        $categories = $this->modelManager->createQueryBuilder()
            ->select('category')
            ->from(Category::class, 'category')
            ->where('category.blog = 1')
            ->andWhere('category.active = 1')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_OBJECT);
        /** @var Category $category */
        $category = $categories[rand(0, count($categories)-1)];

        $this->blog = $this->getMockObjectGenerator()->getMockForAbstractClass(Blog::class);
//        $this->blog = new Blog();
        $this->blog->setTitle('Test das Blog Article Some More Words Title von Donaudampfschifffahrtselektrizitätenhauptbetriebswerkbauunterbeamtengesellschaft');
        $this->blog->setActive(true);
        $this->blog->setAuthor($author);
        $this->blog->setShortDescription('Ich liebe die Adventszeit. Auf dem Weihnachtsmarkt duftet es nach leckeren Köstlichkeiten und überall strahlen die bunten Weihnachtslichter und Wurst');
        $this->blog->setDescription($this->html);
        $this->blog->setDisplayDate(new \DateTime());
        $this->blog->setCategory($category);
        $this->blog->setCategoryId($category->getId());
        $this->blog->setTemplate('Standard');

//        try {
//            $this->modelManager->persist($this->blog);
//            $this->modelManager->flush();
//        } catch (OptimisticLockException $exception) {
//            throw new \RuntimeException('Meh, blog model not persisted.');
//        }

        $this->document = new \DOMDocument('1.0', 'UTF-8');
        $this->document->loadHTML($this->html);
        $this->nodes = $this->document->getElementsByTagName('p');
        
        try {
            $this->config = Shopware()->Container()
                ->get('shopware.plugin.cached_config_reader')
                ->getByPluginName(PhagResponsiveContentInjector::NAME);
        } catch (\Exception $exception) {
            throw new \RuntimeException('Meh, config is missing.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

//        $this->modelManager->createQueryBuilder()
//            ->delete(Blog::class, 'blog')
//            ->where('blog.id = :id')
//            ->setParameter('id', $this->blog->getId())
//            ->getQuery()
//            ->execute();
    }

    public function testGetDefaultConfig()
    {
        $this->assertEquals(self::DEFAULT_CONFIG_ITEMS_COUNT, count($this->config));
    }

    public function testFinalVariables()
    {
        $repository = $this->modelManager->getRepository(Model::class);
        $this->assertInstanceOf(FinalVariable::class, $repository->finalVariables);
        try {
            $this->assertEquals(self::DEFAULT_FINAL_VAR_COUNT, $repository->finalVariables->count());
        } catch (ORMException $exception) {
            throw new \RuntimeException('Meh, this should not happen.');
        }
    }

    /**
     * @depends testGetDefaultConfig
     * @depends testFinalVariables
     */
    public function test()
    {
        $this->Request()
            ->setModuleName('frontend')
            ->setControllerName('blog')
            ->setActionName('detail')
            ->setParams([
                'sCategory' => $this->blog->getCategoryId(),
                'blogArticle' => $this->blog->getId(),
            ]);
        try {
            $this->Front()->setRequest($this->Request());
        } catch (\Exception $exception) {
            throw new \RuntimeException('Meh, front request not attached.');
        }

        $this->assertFalse($this->Front()->Request()->isDispatched());

        $pseudoRoute = '/' . $this->Request()->getControllerName() . '/'
            . $this->Request()->getActionName() . '/sCategory/'
            . $this->Request()->getParam('sCategory') . '/blogArticle/'
            . $this->Request()->getParam('blogArticle');

//        @todo: figure out why Router is not routing, some struct-thingy-issue?
//        $route = $this->Front()->Router()->assemble([
//            'sViewport' => 'blog',
//            'sCategory' => $this->blog->getCategoryId(),
//            'action' => 'detail',
//            'blogArticle' => $this->blog->getId(),
//        ]);
//        $result = $this->Front()->dispatch();

        $response = $this->dispatch($pseudoRoute);
        $this->assertEquals(200, $response->getHttpResponseCode());
        $this->assertFalse($response->isException());
        $this->assertFalse($response->isRedirect());
        $this->assertGreaterThanOrEqual(100, strlen($response->getBody()));

        /** @var Repository $repository */
        $repository = $this->modelManager->getRepository(Model::class);
        $this->assertInstanceOf(
            \Shopware_Proxies_PhagResponsiveContentInjectorModelsRepositoryProxy::class,
            $repository
        );

        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadHTML($response->getBody());

        /**
         * Response, assumable transformed nodes.
         */
        $xpath = new \DOMXPath($document);
        $preNodes = $xpath->query($repository->finalVariables->wysiwygEditorXpath);
        $dump = $document->saveHTML();

        foreach ($preNodes['images'] as $image) {
            $ja = $image;
        }

        /**
         * Source nodes from the fixture.
         */
        foreach ($this->nodes as $key => $node) {
            $hasProduct = \strlen($node->nodeValue) > 2 && \strlen($node->nodeValue) < 10 &&
                $node->nodeValue !== $this->config['pictureHashTag'] &&
                $node->nodeValue !== $this->config['productHashTag'] &&
                !(bool)preg_match($repository->finalVariables->layoutIdRegex, $node->nodeValue);
        }
        
//        $result = $this->Front()->Router()->route($this->Request());
//        $dump = $this->Front()->Router()->assemble();
//        $result = $this->dispatch($shopUrl);
    }
}

/**
 * Class BlogContentProvider
 * @package PhagResponsiveContentInjector\Tests
 */
class BlogContentProvider implements BlogContentProviderInterface {
    const XPATH_MOCK_IMAGE_QUERY = '/html/body//img[contains(@class, "mock")]';
    
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $filesystemMock;

    /**
     * @var MediaService
     */
    protected $mediaService;

    /**
     * @var bool|string 
     */
    protected $html;

    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * @var \DOMXPath
     */
    protected $xpath;

    /**
     * @var \DOMNodeList
     */
    protected $nodes;

    /**
     * @var bool
     */
    protected $debug = true;
    

    public function __construct()
    {
        $this->mediaService = Shopware()->Container()->get('shopware_media.media_service');
        $this->html = file_get_contents(__DIR__ . '/_fixtures/sample_blog.html');
        $this->document = new \DOMDocument('1.0', 'UTF-8');
        $this->document->loadHTML($this->html);
        $this->xpath = new \DOMXPath($this->document);
        $this->nodes = $this->xpath->query(self::XPATH_MOCK_IMAGE_QUERY);
        if ($this->debug) { $dump = $this->document->saveHTML(); }
        
    }

    public function init()
    {
//        $this->filesystemMock = $this->getMockBuilder(File::class)
//            ->disableOriginalConstructor()
//            ->setMethods(['writeStream', 'readStream', 'delete'])
//            ->getMock();
//        $this->mediaService->writeStream('media/image');
    }

    /**
     * Returns a builder object to create mock objects using a fluent interface.
     *
     * @param string|string[] $className
     * @return \PHPUnit_Framework_MockObject_MockBuilder
     */
    private function getMockBuilder($className): \PHPUnit_Framework_MockObject_MockBuilder
    {
        return new \PHPUnit_Framework_MockObject_MockBuilder($this, $className);
    }

}

/**
 * Interface BlogContentProviderInterface
 * @package PhagResponsiveContentInjector\Tests
 */
interface BlogContentProviderInterface
{
    public function init();
}