<?php

class ScreenResponseFoo extends \craft\web\Controller
{
    public function actionFoo(): \yii\web\Response {
        return $this->asCpScreen()
            ->additionalButtons('')
            ->content('')
            ->notice('')
            ->pageSidebar('')
            ->sidebar('');
    }

    public function actionBar(): void {
        $response = $this->asCpScreen();
        $additionalButtons = $response->additionalButtons;
        $content = $response->content;
        $contextMenu = $response->contextMenu;
        $notice = $response->notice;
        $pageSidebar = $response->pageSidebar;
        $sidebar = $response->sidebar;
    }
}

?>
-----
<?php

class ScreenResponseFoo extends \craft\web\Controller
{
    public function actionFoo(): \yii\web\Response {
        return $this->asCpScreen()
            ->additionalButtonsHtml('')
            ->contentHtml('')
            ->noticeHtml('')
            ->pageSidebarHtml('')
            ->metaSidebarHtml('');
    }

    public function actionBar(): void {
        $response = $this->asCpScreen();
        $additionalButtons = $response->additionalButtonsHtml;
        $content = $response->contentHtml;
        $contextMenu = $response->contextMenu;
        $notice = $response->noticeHtml;
        $pageSidebar = $response->pageSidebarHtml;
        $sidebar = $response->metaSidebarHtml;
    }
}

?>
