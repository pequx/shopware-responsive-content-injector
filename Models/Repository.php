<?php

namespace PhagResponsiveContentInjector\Models;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\NonUniqueResultException;
use Shopware\Bundle\ESIndexingBundle\Product\ProductProvider;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ProductService;
use Shopware\Bundle\StoreFrontBundle\Struct\Product;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Blog\Blog;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Shop\Shop;

/**
 * Class Repository
 * @package PhagResponsiveContentInjector\Models
 */
class Repository extends ModelRepository
{
    /**
     * @var Blog
     */
    protected $blog = null;

    /**
     * @var \DOMDocument
     */
    protected $document = null;

    /**
     * @var \DOMNodeList
     */
    protected $nodes = null;

    /**
     * @var array
     */
    protected $content = null;

    /**
     * @var string
     */
    protected $html = null;

    /**
     * @var \DOMXPath
     */
    protected $xpath = null;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $products = null;

    /**
     * @var ProductService
     */
    protected $productService;

    /**
     * @var ContextService
     */
    protected $contextService;

    /**
     * @var ProductProvider
     */
    protected $productProvider;

    /**
     * Final variables container, used for testing and for upcoming features.
     * @var FinalVariable
     */
    public $finalVariables;

    /**
     * Repository constructor.
     * @param EntityManager $em
     * @param Mapping\ClassMetadata $class
     */
    public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);

        $this->config = $this->getConfig();
        try {
            $this->productService = Shopware()->Container()->get('shopware_storefront.product_service');
            $this->contextService = Shopware()->Container()->get('shopware_storefront.context_service');
            $this->productProvider = Shopware()->Container()->get('shopware_elastic_search.product_provider');
        } catch (\Exception $exception) {
            return;
        }

        $this->finalVariables = new FinalVariable([
            'layoutIdRegex',
            'urlPictureRegex',
            'wysiwygEditorXpath'
        ]);

        /**
         * Describers the layout string match.
         * @var string
         */
        $this->finalVariables->layoutIdRegex = '/' . $this->config['layoutHashTag'] . ' (\d)/is';

        /**
         * Describes the url string match with a piture link.
         * @var string
         */
        $this->finalVariables->urlPictureRegex = '/^(http:\/\/|https:\/\/)\S+(jpg|gif|png|jpeg)$/';

        /**
         * Describes string match for source html.
         * @var string
         */
        $this->finalVariables->wysiwygEditorXpath = '/html/body//img[contains(@class, "'.$this->config['wysiwygEditorResponsiveClass'].'")]'.
            '|/html/body//p[contains(@class, "'.$this->config['wysiwygEditorProductClass'].'")]';
    }

    /**
     * Getter method for a plugin config.
     *
     * @return array|boolean
     */
    public function getConfig()
    {
        try {
            $config = Shopware()->Container()
                ->get('shopware.plugin.cached_config_reader')
                ->getByPluginName('PhagResponsiveContentInjector');
            if (!$config) { return false; }
        } catch (\Exception $exception) { return false; }
        return $config;
    }

    /**
     * Setter for content.
     *
     * @param string $name
     * @param string $element
     * @param int $lastParentId
     * @return int|boolean
     */
    public function setContent(string $name, string $element, int $lastParentId)
    {
        try {
            /** @var Blog $blog */
            $blog = $this->getEntityManager()->createQueryBuilder()
                ->select('blog')
                ->from(Blog::class, 'blog')
                ->where('blog.id = :id')
                ->setParameter('id', $lastParentId)
                ->getQuery()
                ->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
            if (!$blog) { return false; }
        } catch (NonUniqueResultException $exception) { return false; }

        /** @var PhagResponsiveContentInjector $model */
        $model = new PhagResponsiveContentInjector();
        $model->setIsActive(true);
        $model->setName($name);
        $model->setElement($element);
        $model->setCreatedAt(new \DateTime());
        $model->setRenderCount(+1);
        $model->setLastParent($blog);
        $model->setViewCount(0);

        /** @var EntityManager $entityManager */
        $entityManager = $this->getEntityManager();

        try {
            $entityManager->persist($model);
            $entityManager->flush();
            return $model->getId();
        } catch (\Exception $exception) { return false; }
    }

    /**
     * Updates the blog entity with a content.
     *
     * @return boolean
     */
    protected function updateBlog(): bool
    {
        if (!$this->html) { return false; }
        $type = gettype($this->blog);
        if ($type === 'array') {
            $this->blog['description'] = $this->html;
            return true;
        }
        $this->blog->setDescription($this->html);

        try {
            $this->getEntityManager()->persist($this->blog);
            $this->getEntityManager()->flush();
            return true;
        } catch (\Exception $exception) { return false; }
    }

    /**
     * Getter method for content (responsive snippet) to be injected into blog entity.
     *
     * @return boolean
     */
    public function getContent(): bool
    {
        $entityType = gettype($this->blog);
        if ($entityType === 'array') {
            $id = $this->blog['id'];
        } else if ($entityType === 'object') {
            $id = $this->blog->getId();
        } else { return false; }
        $this->content = $this->getEntityManager()->createQueryBuilder()
            ->select('model')
            ->from(PhagResponsiveContentInjector::class,'model')
            ->where('model.lastParentId = :blogId')
            ->setParameter('blogId', $id)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_OBJECT);
        if (!$this->content) { return false; }

        return true;
    }

    /**
     * Checks if any of contents needs to be updated after backend edit
     * and performs other nessecary logic checks.
     *
     * @param array $blog
     * @return bool
     */
    public function checkRenderContent(array $blog): bool
    {
        $this->blog = $blog;
        $isContent = $this->getContent();
//        $isBlog = $this->getBlog();

        if (!$isContent) { return true; }

        foreach ($this->content as $model) {
            $type = gettype($model);
            if ($type === 'array') {
                /** @var array $model */
                $isRender = $model['active'] === false;
            } else if ($type === 'object') {
                /** @var PhagResponsiveContentInjector $model */
                $isRender = $model->getActive() === false;
            }
            if ($isRender) { return true; }
        }
        return false;
    }

    /**
     * Associates the blog entity with a content.
     *
     * @return boolean
     */
    public function associateContent(): bool
    {
        if ($this->content) {
            /**
             * @var integer $key
             * @var PhagResponsiveContentInjector $element
             */
            foreach ($this->content as $key => $element) {
                try { //remove current content
                    $current = $this->getEntityManager()->createQueryBuilder()
                        ->select('model')
                        ->from(PhagResponsiveContentInjector::class, 'model')
                        ->where('model.id = :id')
                        ->setParameter('id', $element->getId())
                        ->getQuery()
                        ->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
                    $this->getEntityManager()->remove($current);
                    $this->getEntityManager()->flush();
                } catch (\Exception $exception) { return false; }
            }
        }
        if(!$this->blog) { return false; }

        try {
            /** @var Blog $blog */
            $this->blog = $this->getEntityManager()->createQueryBuilder()
                ->select('blog')
                ->from(Blog::class, 'blog')
                ->where('blog.id = :id')
                ->setParameter('id', $this->blog['id'])
                ->getQuery()
                ->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
            if (!$this->blog) { return false; }
        } catch (NonUniqueResultException $exception) { return false; }

        $isHtml = $this->processHtml();
        if (!$isHtml) { return false; }
//        $isEncoded = $this->checkEncoding();
//        if (!$isEncoded) { return false; }

        $this->document = new \DOMDocument('1.0', 'UTF-8');
        $this->document->loadHTML($this->html);
        $this->nodes = $this->document->getElementsByTagName('p');

        $isGroup = $this->groupNodes();
        if (!$isGroup) { return false; }

        $this->html = null;
        /** @var \DOMElement $node */
        foreach ($this->nodes as $node) {
            $this->html .= $this->document->saveHTML($node);
        }
        if (!$this->html) { return false; }

        $isEncoded = $this->checkEncoding();
        if (!$isEncoded) { return false; }

        return $this->updateBlog();
    }

    /**
     * Helper for node grouping, does the magick.
     *
     * @return boolean
     */
    private function groupNodes(): bool
    {
        $isNodes = $this->nodes->length > 1;
        if (!$isNodes) { return false; }

        $sectionLayoutGroups = [];
        $sectionId = 0;
        $sectionStarted = false;


        /** @var \DOMElement $node */
        foreach ($this->nodes as $key => $node) {
            $isClear = $this->clearNodeAttributes($node);
            if (!$isClear) { return false; }
            if ($this->config['pluginDebugMode']) { $dump = $this->document->saveHTML(); }

            /** @var \DOMElement $nextNode */
            $nextNode = $node->nextSibling;
            $layoutId = null;
            preg_match($this->finalVariables->layoutIdRegex, $nextNode->nodeValue, $layoutId);

            $hasProduct = \strlen($node->nodeValue) > 2 && \strlen($node->nodeValue) < 10 &&
                $node->nodeValue !== $this->config['pictureHashTag'] &&
                $node->nodeValue !== $this->config['productHashTag'] &&
                !(bool)preg_match($this->finalVariables->layoutIdRegex, $node->nodeValue);
            if ($hasProduct) { $hasProduct = $this->checkProduct($node->nodeValue); }
            $hasImageUrl = (bool)preg_match($this->finalVariables->urlPictureRegex, $node->nodeValue);
            $hasImage = $node->firstChild->nodeName === 'img';

            $nextHasProduct = $nextNode->nodeValue && \strlen($nextNode->nodeValue) < 10 &&
                $nextNode->nodeValue !== $this->config['pictureHashTag'] &&
                $nextNode->nodeValue !== $this->config['productHashTag'] &&
                !(bool)preg_match($this->finalVariables->layoutIdRegex, $nextNode->nodeValue);
            if ($nextHasProduct) { $nextHasProduct = $this->checkProduct($nextNode->nodeValue); }
            $nextHasImageUrl = (bool)preg_match($this->finalVariables->urlPictureRegex, $nextNode->nodeValue);
            $nextHasImage = $nextNode->firstChild->nodeName === 'img';

            $sectionStart = (
                    $node->nodeValue === $this->config['pictureHashTag'] ||
                    $node->nodeValue === $this->config['productHashTag']
                ) && ($nextHasImageUrl || $nextHasImage || $nextHasProduct);
            if ($sectionStart) { $sectionStarted = true; }

            $nextSectionEnd = (int)$layoutId[1] > 0 && (!$nextHasImageUrl && !$nextHasImage && !$nextHasProduct);

            if ($sectionStarted) {
                //@todo: change config keys naming convention, this brings a lot of madness here, afterwards it should work.
                if ($hasImageUrl) {
                    $newNode = $node->ownerDocument->createElement('img');
                    $newNode->setAttribute('src', $node->nodeValue);
//                    $newNode->setAttribute('data-src', $node->nodeValue);
                    $newNode->setAttribute('class', $this->config['wysiwygEditorResponsiveClass']);
                    $newNode = $node->ownerDocument->importNode($newNode);
                    $node->appendChild($newNode);
                    $oldNode = $node->childNodes->item(0);
                    $oldNode->parentNode->removeChild($oldNode);
                }
                if ($hasImage) {
                    $node = $node->firstChild;
                    $currentClass = $node->getAttribute('class');
                    $isNoClass = preg_match_all('/'.$this->config['wysiwygEditorResponsiveClass'].'/i', $currentClass) === 0;
                    if ($isNoClass) {
                        $class = $currentClass.' '.$this->config['wysiwygEditorResponsiveClass'];
                        $node->setAttribute('class', $class);
                    }
                }
                if ($hasProduct) {
                    /** @var Product $product */
                    $product = $this->products[$node->nodeValue];
                    $sku = $product->getNumber();
                    $node->setAttribute('data-product-sku', $sku);
                    $currentClass = $node->getAttribute('class');
                    $isNoClass = preg_match_all('/'.$this->config['wysiwygEditorProductClass'].'/i', $currentClass) === 0;
                    if ($isNoClass) {
                        $node->setAttribute('class', $this->config['wysiwygEditorProductClass']);
                    }
                }
                $node->setAttribute('data-section-id', $sectionId);

                if ($nextSectionEnd) {
                    $sectionLayoutGroups[$sectionId] = (int)$layoutId[1];
                    ++$sectionId;
                    $sectionStarted = false;
                }
            }
        }

        if ($this->config['pluginDebugMode']) { $dump = $this->document->saveHTML(); }

        $this->xpath = new \DOMXPath($this->document);
        $this->nodes = $this->xpath->query($this->finalVariables->wysiwygEditorXpath);
        $isNodes = $this->nodes->length > 0;
        if (!$isNodes) { return false; }

        $nodeGroup = [];
        foreach ($this->nodes as $node) {
            if ($this->config['pluginDebugMode']) { $dump = $node->getAttribute('src'); }
            $nodeGroup[(int)$node->getAttribute('data-section-id')][] = $node;
        }

        $sectionGroup = []; //section -> content id
        foreach ($nodeGroup as $key => $section) {
            $this->content = $this->setContent(
                $this->getContentName($this->blog->getTitle()),
                $this->getView($section),
                $this->blog->getId()
            );
            if (!$this->content) { return false; }
            $sectionGroup[$key] = $this->content;
        } //associate groups with id indexes (in db)

        foreach ($sectionGroup as $key => $contentId) {
            if ($this->config['pluginDebugMode']) { $dump = $nodeGroup[$key]; }
            /** @var \DOMElement $element */
            foreach ($nodeGroup[$key] as $element) {
                $element->setAttribute('data-content-id', $contentId);
                $element->setAttribute('data-layout-id', $sectionLayoutGroups[$key]);
            }
        }

        if ($this->config['pluginDebugMode']) { $dump = $this->document->saveHTML(); }

        $this->nodes = $this->document->getElementsByTagName('body');

        return true;
    }

    /**
     * Helper method to clean up a node from previous attributes.
     *
     * @param \DOMElement $node
     * @return bool
     */
    protected function clearNodeAttributes(\DOMElement $node): bool
    {
        if (!$node) {return false;}
        $node->removeAttribute('data-node-id');
        $node->removeAttribute('data-section-id');
        $isClass = $node->getAttribute('class') === $this->config['wysiwygEditorProductClass'];
        if ($isClass) {
            $node->removeAttribute('class');
        }
        return true;
    }

    /**
     * Process and normalize the html string.
     *
     * @return boolean
     */
    public function processHtml(): bool
    {
        $type = gettype($this->blog);
        if ($type === 'array') {
            $this->html = $this->blog['description'];
        } else if ($type === 'object') {
            $this->html = $this->blog->getDescription();
        }
        $this->checkEncoding();
        if (!$this->html) { return false; }
        return true;
    }

    /**
     * Checks if given product exists over sku.
     *
     * @param $sku
     * @return boolean
     */
    protected function checkProduct($sku): bool
    {
        if (!$sku) { return false; }
        $context = $this->contextService->getShopContext();
        if (!$context) { return false; }
        $product = $this->productService->get($sku, $context);
        if (!$product) { return false; }

        $this->products[$sku] = $product;
        return true;
    }

    /**
     * Handles german characters encoding.
     *
     * @return boolean
     */
    protected function checkEncoding(): bool
    {
        if (!$this->html) { return false; }
        $this->html = strip_tags($this->html, '<div><p><span><img><ol><ul><li><em><a><pre><code><h1><h2><h3><h4><h5><h6>'); //@todo: move to config
        $this->html = preg_replace('/\n/', '', $this->html);
        # first lambda function in my plugins ^^
        $this->html = preg_replace_callback(['/(%7B)(\S+)(%20|_)(\S+)(%7D)/'],
            function($match) {
                $match[1] = '{';
                $match[3] = ' ';
                $match[5] = '}';
                return $match[1].$match[2].$match[3].$match[4].$match[5];
            }, $this->html);
        $this->html = mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8');

        return true;
    }

    /**
     * Renders the blog view with injected content.
     *
     * @return string|boolean
     */
    public function renderContent()
    {
        $isHtml = $this->processHtml();
        $isContent = $this->processElements();
        if (!$isHtml || !$isContent) { return false; }

        $this->html = $this->getHtml();
        $this->document = new \DOMDocument('1.0', 'UTF-8');
        $this->document->loadHTML($this->html);
        $this->xpath = new \DOMXPath($this->document);

        if ($this->config['pluginDebugMode']) { $dump = $this->document->saveHTML(); }

        $this->nodes = $this->xpath->query(
            '/html/body//p[text()="'.$this->config['pictureHashTag'].'"]'.
            '|/html/body//p[text()="'.$this->config['productHashTag'].'"]'
        );
        /** @var \DOMElement $section */
        foreach ($this->nodes as $key => $section) {
            $new = $this->document->createElement('section');
            $this->html = $this->content[$key]['element'];
            if (!$this->html) { return false; }

            $temp = new \DOMDocument();
            $temp->loadHTML($this->html);

            /** @var \DOMNodeList $children */
            $children = $temp->getElementsByTagName('body');
            /** @var \DOMElement $child */
            foreach ($children as $child){
                $child = $this->processNode($child, 'div');
                $child = $this->document->importNode($child, true);
                $new->appendChild($child);
            }
            $section->parentNode->replaceChild($new, $section);
        }

        if ($this->config['pluginDebugMode']) { $dump = $this->document->saveHTML(); }

        $this->nodes = $this->xpath->query(
            $this->finalVariables->wysiwygEditorXpath .
            '|/html/body//p[contains(text(),"'.$this->config['layoutHashTag'].'")]'
        );

        foreach ($this->nodes as $node) {
            /** @var \DOMElement $node */
            $node->parentNode->removeChild($node);
        }

        //@todo: figure out why some children are not removed

        if ($this->config['pluginDebugMode']) { $dump = $this->document->saveHTML(); }

        $this->html = null;
        $this->nodes = $this->document->getElementsByTagName('body')->item(0)->childNodes;
        foreach ($this->nodes as $node) {
            $this->html .= $this->document->saveHTML($node);
        }

        if (!$this->html) { return false; }
        $isEncoded = $this->checkEncoding();
        if (!$isEncoded) { return false; }

        return $this->html;
    }

    /**
     * Helper method to process a given node by changing its type.
     *
     * @param \DOMElement $oldNode
     * @param string $name
     * @return \DOMElement
     */
    protected function processNode(\DOMElement $oldNode, string $name): \DOMElement
    {
        $newNode = $oldNode->ownerDocument->createElement($name);
        while ($oldNode->firstChild) {
            $newNode->appendChild($oldNode->firstChild);
        }
        $oldNode->parentNode->replaceChild($newNode, $oldNode);
        return $newNode;
    }

    /**
     * Getter method returning current blog html string
     *
     * @return string
     */
    public function getHtml(): string
    {
        $type = gettype($this->blog);
        if ($type === 'array') {
            return $this->blog['description'];
        }
        return $this->blog->getDescription();
    }

    /**
     * Method generates responsive views.
     *
     * @param array $section
     * @return string html
     */
    protected function getView(array $section): string
    {
        $items = null;
        $layout = null;
        /** @var int $type (0: image, 1: product, 2: comment) */
        $type = null;
        /** @var \DOMElement $element */
        foreach ($section as $element) {
            $class = $element->getAttribute('class');
            $isImageUrl = $class === $this->config['wysiwygEditorResponsiveClass'];
            $isImage = (bool)preg_match('/'.$this->config['wysiwygEditorImageClass'].'/is', $class);
            $isProduct = (bool)preg_match('/'.$this->config['wysiwygEditorProductClass'].'/is', $class);

            if ($isImageUrl) {
                $items[] = $element->getAttribute('src');
                $type = 0;
                //@todo: check how mixed node types works here, eg. prodcuts with pictures.
            }
            if ($isImage) {
                $items[] = $element->getAttribute('data-src');
                $type = 1;
            }
            if ($isProduct) {
                $sku = $element->getAttribute('data-product-sku');
                $this->hydrateProduct($sku);
                $items[] = $this->products[$sku];
                $type = 2;
            }
            $layout = $element->getAttribute('data-layout-id');
        }

        $view = new \Enlight_View_Default(Shopware()->Template());
        $template = $view
            ->createTemplate('widgets/phag_responsive_content_injector/detail.tpl')
            ->Template()
            ->assign([
                'items' => $items,
                'layout' => $layout,
                'type' => $type,
                'debug' => $this->config['pluginDebugMode'] ? true : false,
            ]);

        $this->html = $view->setTemplate($template)->render();
        $this->checkEncoding();

        return $this->html;
    }

    /**
     * Product data hydrator for the template rendering.
     *
     * @param $sku
     * @return bool
     */
    protected function hydrateProduct($sku): bool
    {
        /** @var Product $product */
        $product = $this->products[$sku];
        if (!$product) { return false; }
        if (gettype($product) == 'array') { return true; }
        $id = $product->getId();
        $product = Shopware()->Modules()->Articles()->sGetArticleById($id);
        if (!$product) { return false; }

        $this->products[$sku] = $product;
        return true;
    }

    /**
     * Provides content for view render.
     *
     * @return array|boolean
     */
    public function processElements(): bool
    {
        $isContent = $this->getContent();
        if (!$isContent) { return false; }

        $data = [];

        /** @var PhagResponsiveContentInjector $model */
        foreach ($this->content as $model) {
            $type = gettype($model);
            if ($type === 'array') {
                $id = $model['id'];
                $element = $model['element'];
            } else if ($type === 'object') {
                $id = $model->getId();
                $element = $model->getElement();
            } else {
                return false;
            }

            $data[] = [
                'id' => $id,
                'element' => $element,
            ];
        }
        $this->content = $data;
        return true;
    }

    /**
     * Getter method for content name based on top keywords.
     *
     * @param string $name
     * @return string
     */
    private function getContentName($name): string
    {
        mb_internal_encoding('UTF-8');

        $wordBlacklist = [];
        $string = preg_replace('/[\pP]/u', '',
            trim(preg_replace('/\s\s+/iu', '',
                    mb_strtolower($name))
            ));

        $match = array_filter(explode(' ',$string),
            function ($item) use ($wordBlacklist) {
                return !($item == '' || in_array($item, $wordBlacklist) ||
                    mb_strlen($item) <= 2 || is_numeric($item));
            });

        $wordCount = array_count_values($match);
        arsort($wordCount);

        $wordKeys = array_keys(array_slice($wordCount, 0, 5));

        return implode('_', $wordKeys).'_'.date('H').'_'.date('i');
    }
}

/**
 * Class FinalVariable
 * @package PhagResponsiveContentInjector\Models
 */
class FinalVariable {
    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var int
     */
    protected $count = 0;

    public function __construct(array $variables)
    {
        $this->variables;
    }

    public function __set($var, $value)
    {
        if (array_key_exists($var, $this->variables)) {
            throw new \LogicException("Variable $var is read-only");
        } else {
            $this->variables[$var] = $value;
            $this->count++;
        }
    }

    public function __get($var)
    {
        return array_key_exists($var, $this->variables) ? $this->variables[$var] : null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }
}

