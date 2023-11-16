<?php

namespace Webkul\Marketing\Listeners;

use Webkul\CMS\Repositories\PageRepository;
use Webkul\Marketing\Repositories\URLRewriteRepository;

class Page
{
    /**
     * Permanent redirect code
     * 
     * @var int
     */
    const PERMANENT_REDIRECT_CODE = 301;

    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(
        protected PageRepository $pageRepository,
        protected URLRewriteRepository $urlRewriteRepository
    )
    {
    }

    /**
     * After page is created
     *
     * @param  \Webkul\CMS\Contracts\Page  $page
     * @return void
     */
    public function afterCreate($page)
    {
        /**
         * Delete if url rewrite already exists for request path
         */
        $this->urlRewriteRepository->deleteWhere([
            'entity_type'  => 'cms_page',
            'request_path' => $page->url_key,
            'locale'       => app()->getLocale(),
        ]);
    }

    /**
     * Before page is updated
     *
     * @param  integer  $id
     * @return void
     */
    public function beforeUpdate($id)
    {
        $locale = request()->input('locale');

        $page = $this->pageRepository->find($id);

        $translations = $page->translate($locale);

        /**
         * If url key is empty for requested locale then return
         */
        if (empty($translations['url_key'])) {
            return;
        }

        $currentURLKey = request()->input($locale . '.url_key');

        if ($translations['url_key'] === $currentURLKey) {
            return;
        }

        /**
         * Delete if url rewrite already exists for target path
         */
        $this->urlRewriteRepository->deleteWhere([
            'entity_type' => 'cms_page',
            'target_path' => $translations['url_key'],
            'locale'      => $locale,
        ]);

        $this->urlRewriteRepository->create([
            'entity_type'   => 'cms_page',
            'request_path'  => $translations['url_key'],
            'target_path'   => $currentURLKey,
            'locale'        => $locale,
            'redirect_type' => self::PERMANENT_REDIRECT_CODE,
        ]);
    }

    /**
     * Before page is deleted
     *
     * @param  int  $id
     * @return void
     */
    public function beforeDelete($id)
    {
        $page = $this->pageRepository->find($id);

        /**
         * Delete all url rewrites for all locales
         */
        $translations = $page->getTranslationsArray();

        foreach ($translations as $locale => $translation) {
            $this->urlRewriteRepository->deleteWhere([
                'entity_type'  => 'cms_page',
                'request_path' => $translation['url_key'],
                'locale'       => $locale,
            ]);
        }
    }
}
