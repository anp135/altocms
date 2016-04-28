<?php
/*---------------------------------------------------------------------------
 * @Project: Alto CMS
 * @Project URI: http://altocms.com
 * @Description: Advanced Community Engine
 * @Copyright: Alto CMS Team
 * @License: GNU GPL v2 & MIT
 *----------------------------------------------------------------------------
 * Based on
 *   LiveStreet Engine Social Networking by Mzhelskiy Maxim
 *   Site: www.livestreet.ru
 *   E-mail: rus.engine@gmail.com
 *----------------------------------------------------------------------------
 */

/**
 * @package actions
 * @since   1.0
 */
class ActionBlogs extends Action {
    /**
     * Главное меню
     *
     * @var string
     */
    protected $sMenuHeadItemSelect = 'blogs';

    /**
     * Инициализация
     */
    public function Init() {

        // * Загружаем в шаблон JS текстовки
        E::ModuleLang()->AddLangJs(
            array(
                 'blog_join', 'blog_leave'
            )
        );
    }

    /**
     * Регистрируем евенты
     */
    protected function RegisterEvent() {

        $this->AddEventPreg('/^personal$/i', '/^(page([1-9]\d{0,5}))?$/i', 'EventShowBlogsPersonal');
        $this->AddEventPreg('/^(page([1-9]\d{0,5}))?$/i', 'EventShowBlogs');
        $this->AddEventPreg('/^ajax-search$/i', 'EventAjaxSearch');
    }


    /**********************************************************************************
     ************************ РЕАЛИЗАЦИЯ ЭКШЕНА ***************************************
     **********************************************************************************
     */

    /**
     * Поиск блогов по названию
     */
    protected function EventAjaxSearch() {

        // * Устанавливаем формат Ajax ответа
        E::ModuleViewer()->SetResponseAjax('json');

        // * Получаем из реквеста первые буквы блога
        if ($sTitle = F::GetRequestStr('blog_title')) {
            $sTitle = str_replace('%', '', $sTitle);
        }
        if (!$sTitle) {
            E::ModuleMessage()->AddErrorSingle(E::ModuleLang()->get('system_error'));
            return;
        }

        // * Ищем блоги
        if (F::GetRequestStr('blog_type') == 'personal') {
            $aFilter = array('include_type' => 'personal');
        } else {
            $aFilter = array(
                'include_type' => E::ModuleBlog()->GetAllowBlogTypes(E::User(), 'list', true),
            );
            $aFilter['exclude_type'] = 'personal';
        }
        $aFilter['title'] = "%{$sTitle}%";
        $aFilter['order'] = array('blog_title' => 'asc');

        $aResult = E::ModuleBlog()->GetBlogsByFilter($aFilter, 1, 100);

        // * Формируем и возвращаем ответ
        $aVars = array(
            'aBlogs'          => $aResult['collection'],
            'oUserCurrent'    => E::User(),
            'sBlogsEmptyList' => E::ModuleLang()->get('blogs_search_empty'),
        );
        E::ModuleViewer()->AssignAjax('sText', E::ModuleViewer()->Fetch('commons/common.blog_list.tpl', $aVars));
    }

    protected function EventIndex() {

        $this->EventShowBlogs();
    }

    /**
     * Отображение списка блогов
     */
    protected function EventShowBlogs() {

        // * По какому полю сортировать
        $sOrder = F::GetRequestStr('order', 'blog_rating');

        // * В каком направлении сортировать
        $sOrderWay = F::GetRequestStr('order_way', 'desc');

        // * Фильтр поиска блогов
        $aFilter = array(
            'include_type' => E::ModuleBlog()->GetAllowBlogTypes(E::User(), 'list', true),
        );
        if ($sOrder == 'blog_title') {
            $aFilter['order'] = array('blog_title' => $sOrderWay);
        } else {
            $aFilter['order'] = array($sOrder => $sOrderWay, 'blog_title' => 'asc');
        }

        // * Передан ли номер страницы
        $iPage = preg_match('/^\d+$/i', $this->GetEventMatch(2)) ? $this->GetEventMatch(2) : 1;

        // * Получаем список блогов
        $aResult = E::ModuleBlog()->GetBlogsByFilter(
            $aFilter,
            $iPage, Config::Get('module.blog.per_page')
        );
        $aBlogs = $aResult['collection'];

        // * Формируем постраничность
        $aPaging = E::ModuleViewer()->MakePaging(
            $aResult['count'], $iPage, Config::Get('module.blog.per_page'), Config::Get('pagination.pages.count'),
            R::GetLink('blogs'), array('order' => $sOrder, 'order_way' => $sOrderWay)
        );

        //  * Загружаем переменные в шаблон
        E::ModuleViewer()->assign('aPaging', $aPaging);
        E::ModuleViewer()->assign('aBlogs', $aBlogs);
        E::ModuleViewer()->assign('sBlogOrder', htmlspecialchars($sOrder));
        E::ModuleViewer()->assign('sBlogOrderWay', htmlspecialchars($sOrderWay));
        E::ModuleViewer()->assign('sBlogOrderWayNext', ($sOrderWay == 'desc' ? 'asc' : 'desc'));
        E::ModuleViewer()->assign('sShow', 'collective');
        E::ModuleViewer()->assign('sBlogsRootPage', R::GetLink('blogs'));

        // * Устанавливаем title страницы
        E::ModuleViewer()->AddHtmlTitle(E::ModuleLang()->get('blog_menu_all_list'));

        // * Устанавливаем шаблон вывода
        $this->SetTemplateAction('index');
    }

    /**
     * Отображение списка персональных блогов
     */
    protected function EventShowBlogsPersonal() {

        // * По какому полю сортировать
        $sOrder = F::GetRequestStr('order', 'blog_title');

        // * В каком направлении сортировать
        $sOrderWay = F::GetRequestStr('order_way', 'desc');

        // * Фильтр поиска блогов
        $aFilter = array(
            'include_type' => 'personal'
        );

        // * Передан ли номер страницы
        $iPage = preg_match('/^\d+$/i', $this->GetParamEventMatch(0, 2)) ? $this->GetParamEventMatch(0, 2) : 1;

        // * Получаем список блогов
        $aResult = E::ModuleBlog()->GetBlogsByFilter(
            $aFilter, array($sOrder => $sOrderWay), $iPage, Config::Get('module.blog.per_page')
        );
        $aBlogs = $aResult['collection'];

        // * Формируем постраничность
        $aPaging = E::ModuleViewer()->MakePaging(
            $aResult['count'], $iPage, Config::Get('module.blog.per_page'), Config::Get('pagination.pages.count'),
            R::GetLink('blogs') . 'personal/', array('order' => $sOrder, 'order_way' => $sOrderWay)
        );

        // * Загружаем переменные в шаблон
        E::ModuleViewer()->assign('aPaging', $aPaging);
        E::ModuleViewer()->assign('aBlogs', $aBlogs);
        E::ModuleViewer()->assign('sBlogOrder', htmlspecialchars($sOrder));
        E::ModuleViewer()->assign('sBlogOrderWay', htmlspecialchars($sOrderWay));
        E::ModuleViewer()->assign('sBlogOrderWayNext', ($sOrderWay == 'desc' ? 'asc' : 'desc'));
        E::ModuleViewer()->assign('sShow', 'personal');
        E::ModuleViewer()->assign('sBlogsRootPage', R::GetLink('blogs') . 'personal/');

        // * Устанавливаем title страницы
        E::ModuleViewer()->AddHtmlTitle(E::ModuleLang()->get('blog_menu_all_list'));

        // * Устанавливаем шаблон вывода
        $this->SetTemplateAction('index');
    }

    public function EventShutdown() {

        E::ModuleViewer()->assign('sMenuHeadItemSelect', $this->sMenuHeadItemSelect);
    }
}

// EOF
