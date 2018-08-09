<?php

use \PhagResponsiveContentInjector\Models\PhagResponsiveContentInjector;
use \Shopware\Models\Blog\Blog;
use \Doctrine\ORM\AbstractQuery;

/**
 * Backend controllers extending from Shopware_Controllers_Backend_Application do support the new backend components
 */
class Shopware_Controllers_Backend_PhagResponsiveContentInjector extends Shopware_Controllers_Backend_Application
{
    protected $model = '\PhagResponsiveContentInjector\Models\PhagResponsiveContentInjector';
    protected $alias = 'phag_responsive_content_injector';

    /**
     * @var bool
     */
    protected $isBlogArticleValid;

    /**
     * Happens after post dispatch, catches it and process.
     */
    public function saveBlogArticleAction()
    {
        $this->validateBlogArticle();

        if(!$this->isBlogArticleValid) {
            return $this->View()->assign([
                'success' => false,
                'message' => 'Syntax error.'
            ]);
        }

        try {
            $response = $this->Response();
            $id = $this->Request()->getParam('id');
            $post = $this->Request()->getPost();
            if (!$id) { return; }

            $blog = $this->getBlog($id);
            if (!$blog['success']) { return; }

            /** @var array  $content */
            $content = $this->getModelManager()->createQueryBuilder()
                ->select('content')
                ->from(PhagResponsiveContentInjector::class, 'content')
                ->where('content.lastParent = :blog')
                ->setParameter('blog', $blog['result'])
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_OBJECT);
        } catch (Exception $exception) {
            return;
        }

        $isContent = count($content) > 0;
        if(!$isContent) {
//            return $this->View()->assign(['success' => true, 'data' => $blog['result']]);
            $this->Request()->setDispatched(true);
            $this->forward('saveBlogArticle', 'blog', 'backend');
        }

        $ids = [];
        /** @var PhagResponsiveContentInjector $section */
        foreach ($content as $section) {
            /** @var \Shopware\Models\Blog\Blog $parent */
            $parent =  $section->getLastParent();
            $ids[] = $parent->getId();
            $section->setIsActive(false); //sets false, which means the content should be re-rendered in the view.

            try {
                $this->getModelManager()->persist($section);
                $this->getModelManager()->flush();
            } catch (Exception $exception) {
                return;
            }
        }

        $this->Request()->setDispatched(true);
        $this->forward('saveBlogArticle', 'blog', 'backend');
//        $this->View()->assign(['success' => true, 'data' => $blog]);
    }

    /**
     * Helper method to validate if the gui editor syntax is correct.
     */
    protected function validateBlogArticle()
    {
        $content = $this->Request()->getPost('description');
        if (!$content) { $this->isBlogArticleValid = false; }

        $content = preg_replace('/\n/', '', $content);

        /** @var \PhagResponsiveContentInjector\Models\Repository $repository */
        $repository = $this->getRepository();

        strip_tags($content, $repository->finalVariables->allowedHtmlTags);

        //Checks for any string which may match our hastags, but ignores twitter hashtags.
        $longestHashStringCount = 6;
        $abstractHashRegex = '/##?\s{1}[A-Z][a-z]{1,'.$longestHashStringCount.'}/im';

        $config = $repository->getConfig();
        if (!$config) { return; }

        //Gets anything which may fit into the hashtag.
        $abstractMatchCount = preg_match_all($abstractHashRegex, $content, $abstractMatches);
        if ($abstractMatchCount === 0) { $this->isBlogArticleValid = true; } //nothing to check

//        $twitterHashRegex = '/\B#\w*[a-zA-Z]+\w*/im';
//        $twitterHashCount = preg_match_all($twitterHashRegex, $content);

        /**
         * Checks if `anything` fits ito one of defined hashtags.
         * Later we need to check the pairs, so each hashtag must have one layout tag enclosing it:
         * productHashTag/pictureHashTag -> layoutHashTag are allowed
         * layoutHashTag/productHashTag -> layoutHashTag and so on are not allowed
         */
        $abstractMatches = $abstractMatches[0];
        $concreteMatches = [];
        foreach ($abstractMatches as $index => $match) {
            $concreteMatches['productHashTag'] += preg_match_all('/'.$config['productHashTag'].'/im', $match);
            $concreteMatches['pictureHashTag'] += preg_match_all('/'.$config['pictureHashTag'].'/im', $match);
            $concreteMatches['layoutHashTag'] +=  preg_match_all('/'.$config['layoutHashTag'].'/im', $match);
        }

        $isConcreteCountEqual = $concreteMatches['layoutHashTag'] === $concreteMatches['pictureHashTag'] + $concreteMatches['productHashTag'];
        if (!$isConcreteCountEqual) { $this->isBlogArticleValid = false; }

        /**
         * This part is supposed to check presence of the content blocks between tags.
         * It will not find more complex issues in the code, but will cover wrong <p> tags and inline tags.
         */
        $contentMatches = [
            'pictureSection' => preg_match_all(
                '/'.$config['pictureHashTag'].'<\/p><p.*?>.*?'.$config['layoutHashTag'].'\s\d{1}<\/p>/',
                $content
            ),
            'productSection' => preg_match_all(
                '/'.$config['productHashTag'].'<\/p><p.*?>.*?'.$config['layoutHashTag'].'\s\d{1}<\/p>/',
                $content
            )
        ];

        $isPictureContentCountEqual = $contentMatches['pictureSection'] === $concreteMatches['pictureHashTag'];
        $isProductContentCountEqual = $contentMatches['productSection'] === $concreteMatches['productHashTag'];

        $isValid = $isPictureContentCountEqual && $isProductContentCountEqual && $isConcreteCountEqual;

        if (!$isValid) {
            $this->isBlogArticleValid = false;
        } else {
            $this->isBlogArticleValid = true;
        }
    }

    /**
     * Sets failure to the view.
     */
    protected function setFailure()
    {
        $this->View()->assign([
                'success' => false,
                'message' => 'Syntax error. Please check the content of the blog for typos in the hashtags.'
            ] //@todo: figure out why message is not passed to the view.
        );
    }

    /**
     * Forwards the request to orginal controller.
     */
    protected function setSuccess()
    {

    }

    /**
     * @param int $id
     * @return array
     */
    protected function getBlog(int $id): array
    {
        try {
            /** @var \Shopware\Models\Blog\Blog $blog */
            $blog = $this->getModelManager()->createQueryBuilder()
                ->select(['blog'])
                ->from(Blog::class, 'blog')
                ->where('blog.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getOneOrNullResult(AbstractQuery::HYDRATE_OBJECT);
        } catch (Exception $exception) {
            return [
                'success' => false,
                'result' => $exception->getMessage(),
            ];
        }
        if (!$blog) {
            return [
                'success' => false,
                'result' => 'No blog.'
            ];
        }
        return [
            'success' => true,
            'result' => $blog,
        ];
    }
}
