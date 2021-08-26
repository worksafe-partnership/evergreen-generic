<?php

namespace Evergreen\Generic\App\Http\Controllers;

use DB;
use Gate;
use Auth;
use EGFiles;
use EGForm;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $id;
    protected $args;
    protected $parentId;
    protected $config;
    protected $parentConfig;
    protected $siblings;
    protected $model;
    protected $parentModel;
    protected $datatable = [];
    protected $record;
    protected $parentRecord;
    protected $customValues = [];
    protected $heading;
    protected $view;
    protected $identifier;
    protected $identifierPath;
    protected $splitIdentifierPath;
    protected $pageType;
    protected $pageTypeDescription;
    protected $breadcrumbs;
    protected $pills = [];
    protected $sidebarLinks = [];
    protected $structure;

    protected $formPath;
    protected $fullPath;
    protected $parentPath;
    protected $pagePath;
    protected $createPath;
    protected $editPath;
    protected $cancelPath;
    protected $deletePath;
    protected $permanentlyDeletePath;
    protected $restorePath;
    protected $backPath;
    protected $tabPath;

    protected $submitButtonText;
    protected $createButtonText;
    protected $editButtonText;
    protected $cancelButtonText;
    protected $deleteButtonText;
    protected $permanentlyDeleteButtonText;
    protected $restoreButtonText;
    protected $backButtonText;

    protected $disableBack;
    protected $disableCreate;
    protected $disableEdit;
    protected $disableDelete;
    protected $disablePermanentlyDelete = true;
    protected $disableRestore = true;
    protected $disablePillsOnEdit = false;

    protected $updatingMethod = "updating";
    protected $updatedMethod  = "updated";
    protected $creatingMethod = "creating";
    protected $createdMethod  = "created";
    protected $deletingMethod = "deleting";
    protected $deletedMethod  = "deleted";
    protected $restoringMethod = "restoring";
    protected $restoredMethod = "restored";
    protected $permanentlyDeletingMethod  = "permanentlyDeleting";
    protected $permanentlyDeletedMethod = "permanentlyDeleted";

    protected $automaticallySortFiles = true;

    protected $menuLinks = [
        // 'sub' => [
        //     "label" => "",
        //     "order" => 100,
        //     "icon"  => "more_horiz",
        //     "list"  => []
        // ]
    ];
    protected $menuLinkText = "Menu";
    protected $menuLinkIcon = "menu";

    protected $tabLinks = [
        // 'sub' => [
        //     "label" => "",
        //     "order" => 100,
        //     "icon"  => "more_horiz",
        //     "list"  => []
        // ]
    ];

    protected $pillButtons = [
        // 'sub' => [
        //     "label" => "",
        //     "order" => 100,
        //     "icon"  => "more_horiz",
        //     "list"  => []
        // ]
    ];
    protected $pillButtonText = "Actions";
    protected $pillButtonIcon = "more_vert";

    protected $backButton = null;

    protected $actionButtons = [];
    protected $formButtons = [];

    protected $customBlade = null;

    //Toast settings
    protected $toastTimeout = 3000;
    protected $disableToast = false;
    protected $createdMessage = '';
    protected $failedToCreateMessage = '';
    protected $updatedMessage = '';
    protected $failedToUpdateMessage = '';
    protected $deletedMessage = '';
    protected $failedToDeleteMessage = '';
    protected $permanentlyDeletedMessage = '';
    protected $failedToPermanentlyDeleteMessage = '';
    protected $restoredMessage = '';
    protected $failedToRestoreMessage = '';

    protected $user = null;

    public function __construct()
    {
        $this->config = config("structure.".$this->identifierPath.".config");
        $this->structure = config("structure.".$this->identifierPath);
        $this->splitIdentifierPath = explode(".", $this->identifierPath);
        $this->identifier = end($this->splitIdentifierPath);
        array_pop($this->splitIdentifierPath);
        $this->parentConfig = config("structure.".implode(".", $this->splitIdentifierPath).".config");

        $this->siblings = config("structure.".implode(".", $this->splitIdentifierPath));

        unset($this->siblings['config']);
        if (is_null($this->siblings) || count($this->siblings) <= 1) {
            $this->siblings = [];
        }

        if (isset($this->config['db']['model'])) {
            if (substr($this->config['db']['model'], 0, 1) === "\\") {
                $this->model = $this->config['db']['model'];
            } else {
                $this->model = "\App\\".$this->config['db']['model'];
            }
        }
        if (isset($this->parentConfig['db']['model'])) {
            if (substr($this->parentConfig['db']['model'], 0, 1) === "\\") {
                $this->parentModel = $this->parentConfig['db']['model'];
            } else {
                $this->parentModel = "\App\\".$this->parentConfig['db']['model'];
            }
        }
        if (isset($this->config['custom_blade'])) {
            $this->customBlade = $this->config['custom_blade'];
        }

        $this->createdMessage = $this->config['singular'].' has been successfully created';
        $this->failedToCreateMessage = $this->config['singular'].' has failed to be created';
        $this->updatedMessage = $this->config['singular'].' has been successfully updated';
        $this->failedToUpdateMessage = $this->config['singular'].' has failed to be updated';
        $this->deletedMessage = $this->config['singular'].' has been successfully deleted';
        $this->failedToDeleteMessage = $this->config['singular'].' has failed to be deleted';
        $this->permanentlyDeletedMessage = $this->config['singular'].' has been successfully deleted';
        $this->failedToPermanentlyDeleteMessage = $this->config['singular'].' has failed to be deleted';
        $this->restoredMessage = $this->config['singular'].' has been successfully restored';
        $this->failedToRestoreMessage = $this->config['singular'].' has failed to be restored';
    }

    public function setup()
    {
        $this->user = Auth::user();
        if (!is_null($this->user)) {
            $this->user->load('roles');
        }
    }

    public function _index($args = [])
    {
        $this->setup();

        if (!is_array($args) || empty($args)) {
            $args = func_get_args();
        }

        $this->args = $args;
        $this->parentId = end($args);

        $this->pageType = "list";

        $this->_indexHook();

        $this->_buildProperties($args);

        if (empty($this->datatable) && !empty($this->config['datatable'])) {
            $this->datatable = $this->config['datatable'];
            if (empty($this->config['datatable']['href'])) {
                $this->datatable['href'] = $this->fullPath."/%ID%";
            }
            if (empty($this->config['datatable']['data'])) {
                $this->datatable['data'] = $this->fullPath.".datatable.json";
            }
            if (empty($this->config['datatable']['name'])) {
                $this->datatable['name'] = str_replace(".", "-", $this->identifierPath);
            }
            if (empty($this->config['datatable']['serverSide'])) {
                $this->datatable['serverSide'] = "false";
            }
        }

        $this->_postIndexHook();

        return $this->_renderView("layouts.list");
    }

    public function _custom()
    {
        $this->setup();

        $this->pageType = "custom";
        $this->_buildProperties();
        return $this->_renderView("layouts.custom");
    }

    public function _create($args = [])
    {
        $this->setup();

        if (!is_array($args) || empty($args)) {
            $args = func_get_args();
        }

        $this->args = $args;
        $this->parentId = end($args);

        $this->pageType = "create";
        $this->pageTypeDescription = "Creating";

        $this->_bladeHook();
        $this->_createHook();

        $this->_buildProperties($args);

        $this->_postBladeHook();
        $this->_postCreateHook();


        return $this->_renderView("layouts.display");
    }

    public function _store($args = [])
    {
        $this->setup();

        $request = $args[0];
        if (!is_array($request)) {
            $request = $request->all();
        }

        $request = EGForm::checkSelectOthers($request);

        unset($args[0]); // remove the request from the arguments list

        $this->args = $args;
        $this->parentId = end($args);

        $this->_buildProperties($args);

        if (method_exists($this, $this->creatingMethod)) {
            $redirect = $this->{$this->creatingMethod}($request, $args);
            if ($redirect) {
                return redirect($redirect);
            }
        }

        $insert = DB::transaction(function () use ($request) {
            $originalRequest = $request;
            foreach ($request as $key => $item) {
                if (is_array($item) && isset($item[0])) {
                    if (is_object($item[0]) && get_class($item[0]) == "Illuminate\Http\UploadedFile") {
                        unset($request[$key]);
                    }
                } else if (is_object($item) && get_class($item) == "Illuminate\Http\UploadedFile") {
                    unset($request[$key]);
                }
            }

            $record = $this->model::create($request);

            if ($this->automaticallySortFiles) {
                $this->sortFiles($originalRequest, $record);
            }

            return $record;
        });

        if ($insert) {
            if (!$this->disableToast) {
                toast()->success($this->createdMessage)->timeout($this->toastTimeout);
            }

            if (method_exists($this, $this->createdMethod)) {
                $redirect = $this->{$this->createdMethod}($insert, $request, $args);
                if ($redirect) {
                    return redirect($redirect);
                }
            }

            $redirect = $this->fullPath."/".$insert->id;
            return redirect($redirect);
        } else {
            if (!$this->disableToast) {
                toast()->error($this->failedToCreateMessage);
            }
            return back();
        }
    }

    public function _view($args = [])
    {
        $this->setup();

        if (!is_array($args) || empty($args)) {
            $args = func_get_args();
        }
        $this->args = $args;

        $this->_setData($args);
        $this->pageType = "view";
        $this->pageTypeDescription = "Viewing";

        $this->_bladeHook();
        $this->_viewHook();
        $this->_viewEditHook();

        $this->_buildProperties($args);

        $this->_postBladeHook();
        $this->_postViewHook();

        return $this->_renderView("layouts.display");
    }

    public function _edit($args = [])
    {
        $this->setup();

        if (!is_array($args) || empty($args)) {
            $args = func_get_args();
        }

        $this->args = $args;

        $this->_setData($args);
        $this->pageType = "edit";
        $this->pageTypeDescription = "Editing";

        $this->_bladeHook();
        $this->_editHook();
        $this->_viewEditHook();

        $this->_buildProperties($args);

        $this->_postBladeHook();
        $this->_postEditHook();

        return $this->_renderView("layouts.display");
    }

    public function _setData($args)
    {
        if ($this->config['route_type'] == "resource-page" && (!empty($this->parentConfig) && $this->parentConfig['route_type'] != "prefix")) {
            // find the record based on the parent
            $this->parentId = end($args);
            if (usingSoftDeletes($this->parentModel)) {
                $this->parentRecord = $this->parentModel::withTrashed()->findOrFail(end($args));
            } else {
                $this->parentRecord = $this->parentModel::findOrFail(end($args));
            }
        } else {
            $this->id = end($args);
            $this->parentId = prev($args);
            if (!empty($this->model)) {
                if (usingSoftDeletes($this->model)) {
                    $this->record = (isset($this->record) ? $this->record : $this->model::withTrashed()->findOrFail($this->id));
                } else {
                    $this->record = (isset($this->record) ? $this->record : $this->model::findOrFail($this->id));
                }
            }
        }
    }

    public function _update($args = [])
    {
        $this->setup();

        $request = $args[0];
        if (!is_array($request)) {
            $request = $request->all();
        }

        $this->args = $args;

        $request = EGForm::checkSelectOthers($request);

        $this->_setData($args);
        $this->_buildProperties($args);

        $record = $this->record;
        $original = $record->replicate();

        if (method_exists($this, $this->updatingMethod)) {
            $redirect = $this->{$this->updatingMethod}($original, $request, $args);
            if ($redirect) {
                return redirect($redirect);
            }
        }

        $update = DB::transaction(function () use ($request, $record) {
            if ($this->automaticallySortFiles) {
                $request = $this->sortFiles($request, $record);
            }
            $record->update($request);

            return $record;
        });

        if ($update) {
            if (!$this->disableToast) {
                toast()->success($this->updatedMessage)->timeout($this->toastTimeout);
            }
            if (method_exists($this, $this->updatedMethod)) {
                $redirect = $this->{$this->updatedMethod}($update, $original, $request, $args);
                if ($redirect) {
                    return redirect($redirect);
                }
            }
            $redirect = $this->fullPath;
            return redirect($redirect);
        } else {
            if (!$this->disableToast) {
                toast()->error($this->failedToUpdateMessage);
            }
            return back();
        }
    }

    public function _delete($args = [])
    {
        $this->setup();

        if (!is_array($args) || empty($args)) {
            $args = func_get_args();
        }

        $this->args = $args;
        $this->id = end($args);

        $this->_setData($args);
        $this->_buildProperties($args);

        $record = $this->record;
        $original = $record->replicate();

        if (method_exists($this, $this->deletingMethod)) {
            $redirect = $this->{$this->deletingMethod}($original, $args);
            if ($redirect) {
                return redirect($redirect);
            }
        }

        $delete = DB::transaction(function () use ($record) {
            $record->delete();
            return $record;
        });

        if ($delete) {
            if (!$this->disableToast) {
                toast()->success($this->deletedMessage)->timeout($this->toastTimeout);
            }

            if (method_exists($this, $this->deletedMethod)) {
                $redirect = $this->{$this->deletedMethod}($original, $args);
                if ($redirect) {
                    return redirect($redirect);
                }
            }

            $redirect = $this->pagePath;
            return redirect($redirect);
        } else {
            if (!$this->disableToast) {
                toast()->error($this->failedToDeleteMessage);
            }
            return back();
        }
    }

    public function _permanentlyDelete($args = [])
    {
        $this->setup();

        if (!is_array($args) || empty($args)) {
            $args = func_get_args();
        }

        $this->args = $args;

        $this->id = end($args);
        $this->_setData($args);
        $this->_buildProperties($args);

        $record = $this->record;
        $original = $record->replicate();

        if (method_exists($this, $this->permanentlyDeletingMethod)) {
            $redirect = $this->{$this->permanentlyDeletingMethod}($original, $args);
            if ($redirect) {
                return redirect($redirect);
            }
        }

        $delete = DB::transaction(function () use ($record) {
            $record->forceDelete();
            return $record;
        });

        if ($delete) {
            if (!$this->disableToast) {
                toast()->success($this->permanentlyDeletedMessage)->timeout($this->toastTimeout);
            }
            if (method_exists($this, $this->permanentlyDeletedMethod)) {
                $redirect = $this->{$this->permanentlyDeletedMethod}($original, $args);
                if ($redirect) {
                    return redirect($redirect);
                }
            }
            $redirect = $this->pagePath;
            return redirect($redirect);
        } else {
            if (!$this->disableToast) {
                toast()->error($this->failedToPermanentlyDeleteMessage);
            }
            return back();
        }
    }

    public function _restore($args = [])
    {
        $this->setup();

        if (!is_array($args) || empty($args)) {
            $args = func_get_args();
        }

        $this->args = $args;

        $this->id = end($args);
        $this->_setData($args);
        $this->_buildProperties($args);

        $record = $this->record;
        $original = $record->replicate();

        if (method_exists($this, $this->restoringMethod)) {
            $redirect = $this->{$this->restoringMethod}($original, $args);
            if ($redirect) {
                return redirect($redirect);
            }
        }

        $delete = DB::transaction(function () use ($record) {
            $record->restore();
            return $record;
        });

        if ($delete) {
            if (!$this->disableToast) {
                toast()->success($this->restoredMessage)->timeout($this->toastTimeout);
            }
            if (method_exists($this, $this->restoredMethod)) {
                $redirect = $this->{$this->restoredMethod}($original, $args);
                if ($redirect) {
                    return redirect($redirect);
                }
            }
            $redirect = $this->pagePath;
            return redirect($redirect);
        } else {
            if (!$this->disableToast) {
                toast()->error($this->failedToRestoreMessage);
            }
            return back();
        }
    }

    public function _datatableAll()
    {
        $this->setup();

        $args = func_get_args();
        $this->parentId = end($args);

        return $this->model::datatableAll($this->parentId, $this->config);
    }

    public function _bladeHook()
    {
        /**
         * Called on create, view and edit
         */
        if (method_exists($this, "bladeHook")) {
            // call method from a controller
            $this->bladeHook();
        }
    }

    public function _createHook()
    {
        if (method_exists($this, "createHook")) {
            // call method from a controller
            $this->createHook();
        }
    }

    public function _viewHook()
    {
        if (method_exists($this, "viewHook")) {
            // call method from a controller
            $this->viewHook();
        }
    }

    public function _editHook()
    {
        if (method_exists($this, "editHook")) {
            // call method from a controller
            $this->editHook();
        }
    }

    public function _viewEditHook()
    {
        if (method_exists($this, "viewEditHook")) {
            // call method from a controller
            $this->viewEditHook();
        }
    }

    public function _indexHook()
    {
        if (method_exists($this, "indexHook")) {
            // call method from a controller
            $this->indexHook();
        }
    }

    public function _postBladeHook()
    {
        /**
         * Called on create, view and edit
         */

        if (method_exists($this, "postBladeHook")) {
            // call method from a controller
            $this->postBladeHook();
        }
    }

    public function _postCreateHook()
    {
        if (method_exists($this, "postCreateHook")) {
            // call method from a controller
            $this->postCreateHook();
        }
    }

    public function _postViewHook()
    {
        if (method_exists($this, "postViewHook")) {
            // call method from a controller
            $this->postViewHook();
        }
    }

    public function _postEditHook()
    {
        if (method_exists($this, "postEditHook")) {
            // call method from a controller
            $this->postEditHook();
        }
    }

    public function _postIndexHook()
    {
        if (method_exists($this, "postIndexHook")) {
            // call method from a controller
            $this->postIndexHook();
        }
    }

    public function _renderView($view)
    {

        /**
         * Different View Types: [list, display, custom]
         */

        $data = array_merge([
            "id"         => $this->id,
            "parentId"   => $this->parentId,
            "config"     => $this->config,
            "parentConfig" => $this->parentConfig,
            "identifierPath" => $this->identifierPath,
            "identifier" => $this->identifier,
            "datatable"  => $this->datatable,
            "breadcrumbs" => $this->breadcrumbs,
            "heading"    => $this->heading,
            "view"       => $this->view,
            "record"     => $this->record,
            "pageType"   => $this->pageType,
            "sidebarLinks" => $this->sidebarLinks,
            "actionPath" => $this->fullPath,
            "formPath"   => $this->formPath,
            "editPath"   => $this->editPath,
            "cancelPath" => $this->cancelPath,
            "deletePath" => $this->deletePath,
            "backPath"   => $this->backPath,
            "customBlade" => $this->customBlade,
            "submitButtonText" => $this->submitButtonText,
            "cancelButtonText" => $this->cancelButtonText,
            "backButtonText"   => $this->backButtonText,
            "disableCreate"    => $this->disableCreate,
            "disableEdit"      => $this->disableEdit,
            "disableDelete"    => $this->disableDelete,
            "disablePermanentlyDelete"    => $this->disablePermanentlyDelete,
            "disableRestore"    => $this->disableRestore,
            "actionButtons"    => $this->actionButtons,
            "pillButtons"      => $this->pillButtons,
            "pillButtonText"      => $this->pillButtonText,
            "pillButtonIcon"      => $this->pillButtonIcon,
            "menuLinks"      => $this->menuLinks,
            "menuLinkText"      => $this->menuLinkText,
            "menuLinkIcon"      => $this->menuLinkIcon,
            "tabLinks"      => $this->tabLinks,
            "backButton"      => $this->backButton,
            "formButtons"      => $this->formButtons
         ], $this->customValues);

        // if a custom view has been set load the custom layout
        if (!empty($this->view)) {
            $view = "layouts.custom";
        }
        return view("egl::".$view, $data);
    }

    public function sortFiles($request, $record)
    {
        $fileColumns = [];
        $deleteFile = [];
        $deleteFiles = [];

        foreach ($request as $column => $item) {
            if (is_array($item)) {
                foreach ($item as $i) {
                    if (is_object($i) && get_class($i) == "Illuminate\Http\UploadedFile") {
                        $fileColumns[$column] = $column;
                    }
                }
            }
            if (is_object($item) && get_class($item) == "Illuminate\Http\UploadedFile") {
                $fileColumns[$column] = $column;
            }
            if ($item == "on") {
                preg_match("/delete_(.*)/", $column, $matches);
                if (!empty($matches) && isset($matches[1])) {
                    $deleteFile[] = $matches[1];
                }
            } else if (is_array($item)) {
                foreach ($item as $col => $i) {
                    if ($i == "on") {
                        preg_match("/delete_(.*)/", $column, $matches);
                        if (!empty($matches) && isset($matches[1])) {
                            $deleteFiles[$matches[1]][] = $col;
                        }
                    }
                }
            }
        }

        if (!empty($deleteFile)) {
            foreach ($deleteFile as $delete) {
                if (isset($record[$delete])) {
                    $file = EGFiles::find($record[$delete]);
                    if (!is_null($file)) {
                        $file->delete();
                        $record[$delete] = null;
                        $record->save();
                    }
                }
            }
        }

        if (!empty($deleteFiles)) {
            foreach ($deleteFiles as $column => $files) {
                $col = json_decode($record[$column]);
                if (is_object($col)) {
                    $col = (array)$col;
                }

                $newCol = [];
                foreach ($col as $id => $data) {
                    $newCol[intval($id)] = $data;
                }

                $col = $newCol;
                foreach ($files as $delete) {
                    $file = EGFiles::find($delete);
                    if (!is_null($file)) {
                        $file->delete();
                        $key = array_search($delete, $col);
                        if (!is_null($key)) {
                            unset($col[$key]);
                        }
                    }
                }
                $record[$column] = json_encode($col);
                $record->save();
            }
        }

        if (!empty($fileColumns)) {
            $data = EGFiles::store($request, $fileColumns);
            foreach ($data as $column => $value) {
                if (is_array($value)) {
                    $current = json_decode($record[$column]);
                    if (is_object($current)) {
                        $current = (array)$current;
                    }

                    if (!is_array($current)) {
                        $current = [];
                    }
                    $data[$column] = json_encode(array_merge($current, $value));
                }
            }
            $record->update($data);

            foreach ($fileColumns as $column) {
                unset($request[$column]);
            }
        }

        return $request;
    }

    public function _buildProperties($args = [])
    {
        $urlParts = [];
        $arguments = $args;
        if (isset($this->parentId) && !isset($this->id)) {
            // we're on the parent record so set a blank argument
            $currentArg = "";
        } elseif (isset($this->id)) {
            // we must be on a physical record to set the argument and remove from array
            $currentArg = end($arguments);
            array_pop($arguments);
        } else {
            $currentArg = "";
        }

        $currentConfig = $this->config;
        $currentPath = $this->identifierPath;

        // loop around and set the remainder of the tree
        while (true) {
            $splitCurrentPath = array_filter(explode(".", $currentPath));
            $currentIdentifier = end($splitCurrentPath);

            $isCurrentPage = $currentArg == $this->id && $currentIdentifier == $this->identifier ? true : false;

            if ($currentConfig['route_type'] == "resource" && !empty($currentArg)) {
                array_unshift($urlParts, [
                    "path_part" => $currentArg,
                    "identifier_path" => $currentPath,
                    "id"  => $currentArg,
                    "is_current_child" => $isCurrentPage ? true : false,
                ]);
                $currentArg = end($arguments);
            } else {
                if (isset($this->parentId)) {
                    $currentArg = end($arguments);
                }
            }

            array_unshift($urlParts, [
                "path_part" => $currentIdentifier,
                "identifier_path" => $currentPath,
                "is_current_page" => $isCurrentPage,
            ]);

            if (count($splitCurrentPath) > 1) {
                array_pop($splitCurrentPath);
                array_pop($arguments);
                $currentPath = implode(".", $splitCurrentPath);
                $currentConfig = config("structure.".$currentPath.".config");
            } else {
                // can't go up any further so break out of loop
                break;
            }
        }

        // set the url paths
        if (!empty($urlParts)) {
            $fullPath = '';
            $parentPath = '';
            $pagePath = '';
            $tabPath = '';
            $grabId = false;

            foreach ($urlParts as $key => $part) {
                $fullPath .= "/".$part['path_part'];
                if (isset($part['is_current_page']) && $part['is_current_page']) {
                    $this->pagePath = $fullPath;
                }
                $urlParts[$key]['full_path'] = $fullPath;
                $urlParts[$key]['parent_path'] = $parentPath;
                $parentPath .= "/".$part['path_part'];
                if (in_array($part['path_part'], $this->splitIdentifierPath)) {
                    if ($tabPath != '') {
                        $tabPath.= '/';
                    }
                    $tabPath.= $part['path_part'];
                    $grabId = true;
                }

                if ($grabId && isset($part['id'])) {
                    $tabPath.= "/".$part['id'];
                    $grabId = false;
                }
            }


            $this->tabPath = $tabPath;
            $this->fullPath = $fullPath;
            $this->parentPath = end($urlParts)['parent_path']; // take the last parts parent path
        }

        if ((!empty($this->config['disableCreate']) && !isset($this->disableCreate)) || $this->config['route_type'] == "resource-page") {
            $this->disableCreate = true;
        }
        if ((!empty($this->config['disableDelete']) && !isset($this->disableDelete)) || $this->config['route_type'] == "resource-page") {
            $this->disableDelete = true;
        }
        if (!empty($this->config['disableEdit']) && !isset($this->disableEdit)) {
            $this->disableEdit = true;
        }

        if (!can("create", $this->config)) {
            $this->disableCreate = true;
        }

        if (!can("edit", $this->config)) {
            $this->disableEdit = true;
        }

        if (!can("delete", $this->config)) {
            $this->disableDelete = true;
        }

        if (!can("restore", $this->config)) {
            $this->disableRestore = true;
        }

        // build the specific page type elements
        $this->_buildPageTypeSpecifics();

        // build the breadcrumbs
        $this->_buildBreadcrumbs($urlParts);

        // build the sidebar links
        $this->_buildSidebar();

        // build the heading
        $this->_buildHeading();

        // build the action buttons
        $this->_buildActionButtons();

        // build the pill buttons
        $this->_buildPillButtons();

        // build the menu and tab links
        $this->_buildMenuAndTabLinks();

        // build the form buttons
        $this->_buildFormButtons();
    }

    public function _buildPageTypeSpecifics()
    {
        switch ($this->pageType) {
            case "edit":
                $this->formPath = $this->fullPath."/edit";

                if (empty($this->cancelPath)) {
                    $this->cancelPath = $this->fullPath;
                }

                if (empty($this->submitButtonText)) {
                    $this->submitButtonText = "Update ".$this->config['singular'];
                }
                if (empty($this->cancelButtonText)) {
                    $this->cancelButtonText = "Cancel";
                }
                break;
            case "create":
                $this->formPath = $this->fullPath."/create";

                if (empty($this->cancelPath)) {
                    $this->cancelPath = $this->pagePath;
                }

                if (empty($this->submitButtonText)) {
                    $this->submitButtonText = "Create ".$this->config['singular'];
                }
                if (empty($this->cancelButtonText)) {
                    $this->cancelButtonText = "Cancel";
                }
                break;
        }
    }

    public function _buildActionButtons()
    {
        if (in_array($this->pageType, ["list", "view", "edit"])) {
            if (!$this->disableCreate && empty($this->actionButtons["create"])) {
                $label = !empty($this->createButtonText) ? $this->createButtonText : "Create ".$this->config['singular'];
                $this->actionButtons["create"] = [
                    "label" => $label,
                    "path"  => !empty($this->createPath) ? $this->createPath : $this->pagePath."/create",
                    "class" => "is-success",
                    "icon"  => "plus2",
                    "order" => 100,
                    "title" => $label
                ];
            }
        }
        if (in_array($this->pageType, ["edit", "view"])) {
            if (usingSoftDeletes($this->model, $this->record)) {
                $this->disableDelete = true;
                $this->disableRestore = false;
                $this->disablePermanentlyDelete = false;
                $this->disableEdit = true;
            }

            if (!$this->disableDelete && empty($this->actionButtons["view"])) {
                $label = !empty($this->deleteButtonText) ? $this->deleteButtonText : "Delete ".$this->config['singular'];
                $this->actionButtons["delete"] = [
                    "label" => $label,
                    "path"  => !empty($this->deletePath) ? $this->deletePath : $this->fullPath."/delete",
                    "class" => "is-danger",
                    "icon"  => "bin2",
                    "order" => 300,
                    "onclick" => "return confirm('Are you sure you want to delete this ".$this->config['singular']."?')",
                    "title" => $label
                ];
            }

            if (!$this->disablePermanentlyDelete && empty($this->actionButtons["view"])) {
                $label = !empty($this->permanentlyDeleteButtonText) ? $this->permanentlyDeleteButtonText : "Permanently Delete ".$this->config['singular'];
                $this->actionButtons["permanently_delete"] = [
                    "label" => $label,
                    "path"  => !empty($this->permanentlyDeletePath) ? $this->permanentlyDeletePath : $this->fullPath."/permanentlyDelete",
                    "class" => "is-danger",
                    "icon"  => "warning",
                    "order" => 400,
                    "onclick" => "return confirm('Are you sure you want to permanently delete this ".$this->config['singular']."?')",
                    "title" => $label
                ];
            }

            if (!$this->disableRestore && empty($this->actionButtons["view"])) {
                $label = !empty($this->restoreButtonText) ? $this->restoreButtonText : "Restore ".$this->config['singular'];
                $this->actionButtons["restore"] = [
                    "label" => $label,
                    "path"  => !empty($this->restorePath) ? $this->restorePath : $this->fullPath."/restore",
                    "class" => "is-success",
                    "icon"  => "restore",
                    "order" => 500,
                    "onclick" => "return confirm('Are you sure you want to restore this ".$this->config['singular']."?')",
                    "title" => $label
                ];
            }
        }
        if ($this->pageType == "view") {
            if (!$this->disableEdit && empty($this->actionButtons["edit"])) {
                $label = !empty($this->editButtonText) ? $this->editButtonText : "Edit ".$this->config['singular'];
                $this->actionButtons["edit"] = [
                    "label" => $label,
                    "path"  => !empty($this->editPath) ? $this->editPath : $this->fullPath."/edit",
                    "class" => "is-warning",
                    "icon"  => "pencil22",
                    "order" => 200,
                    "title" => $label
                ];
            }
        }

        // reorder buttons
        uasort($this->actionButtons, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });
    }

    public function _buildBreadcrumbs($urlParts)
    {
        // go back down the tree and build the breadcrumbs
        $breadcrumbs = [];
        if (!empty($urlParts) && empty($this->breadcrumbs) && in_array($this->pageType, array("list", "view", "edit", "create", "custom"))) {
            $path = "";
            foreach ($urlParts as $part) {
                $config = config("structure.".$part['identifier_path'].".config");
                $path .= "/".$part['path_part'];

                $link = $config['route_type'] == "prefix" ? false : true;

                if ($config['route_type'] == "module") {
                    continue;
                }

                if (isset($part['id'])) {
                    if (substr($config['db']['model'], 0, 1) === "\\") {
                        $model = $config['db']['model'];
                    } else {
                        $model = "\App\\".$config['db']['model'];
                    }

                    if ($model == $this->model && !empty($this->record)) {
                        $query = $this->record;
                    } else if ($model == $this->parentModel && !empty($this->parentRecord)) {
                        $query = $this->parentRecord;
                    } else {
                        if (usingSoftDeletes($model)) {
                            $query = $model::withTrashed()->findOrFail($part['id']);
                        } else {
                            $query = $model::findOrFail($part['id']);
                        }
                    }
                    $name = $query->{$config['db']['column']};

                    $breadcrumbs[] = [
                        "link" => $link,
                        "name" => $name,
                        "path_part" => $query->{$config['db']['id']},
                        "path" => $path,
                        "icon" => "tab",
                    ];
                } else {
                    $splitIdentifierPath = explode(".", $part['identifier_path']);
                    $name = $config['plural'];

                    $breadcrumbs[] = [
                        "link" => $link,
                        "name" => $name,
                        "path_part" => end($splitIdentifierPath),
                        "path" => $path,
                        "icon" => "format_list_bulleted",
                    ];
                }
            }

            $this->breadcrumbs = array_reverse($breadcrumbs);
        }
    }

    public function _buildMenuAndTabLinks()
    {
        if (empty($this->siblings)) {
            $siblingsEmpty = true;
        }
        if (empty($this->tabLinks)) {
            $tabLinksEmpty = true;
        }
        if (empty($this->menuLinks)) {
            $menuLinksEmpty = true;
        }

        if (isset($siblingsEmpty)) {
            if (isset($this->id)) {
                $label = $this->structure['config']['singular'];
                $current = $this->identifierPath == $this->structure['config']['identifier_path'] ? true : false;
                if (isset($this->structure['config']['tabs']) && can('list', $this->structure['config'])) {
                    if (isset($this->structure['config']['tabs']['label'])) {
                        $label = $this->structure['config']['tabs']['label'];
                    }
                    $this->tabLinks[$label] = [
                        'url' => '#',
                        'order' => 0,
                        'current' => $current
                    ];
                }
                if (isset($this->structure['config']['menu']) && can('list', $this->structure['config'])) {
                    if (isset($this->structure['config']['menu']['label'])) {
                        $label = $this->structure['config']['menu']['label'];
                    }
                    $this->menuLinks[$label] = [
                        'url' => '#',
                        'order' => 0,
                        'current' => $current
                    ];
                }
            }
        } else {
            $label = $this->parentConfig['singular'];
            $current = $this->identifierPath == $this->parentConfig['identifier_path'] ? true : false;

            if (isset($this->parentConfig['tabs']) && can('list', $this->parentConfig)) {
                if (isset($this->parentConfig['tabs']['label'])) {
                    $label = $this->parentConfig['tabs']['label'];
                }
                
                $this->tabLinks[$label] = [
                    'url' => $this->parentPath."/",
                    'order' => 0,
                    'current' => $current
                ];
            }
            if (isset($this->parentConfig['menu']) && can('list', $this->parentConfig)) {
                if (isset($this->parentConfig['menu']['label'])) {
                    $label = $this->parentConfig['menu']['label'];
                }

                if ($this->pageType == "list") {
                    $this->menuLinks[$label] = [
                        'url' => $this->parentPath."/",
                        'order' => 0,
                        'current' => $current
                    ];
                } else {
                    $this->menuLinks[$label] = [
                        'url' => "/".$this->tabPath,
                        'order' => 0,
                        'current' => $current
                    ];
                }
            }
        }

        if (isset($siblingsEmpty)) {
            $structure = $this->structure;
            if (!isset($this->id)) {
                $structure = [];
            }
        } else {
            $structure = $this->siblings;
        }

        foreach ($structure as $key => $child) {
            if (array_key_exists('config', $child)) {
                $current = $this->identifierPath == $child['config']['identifier_path'] ? true : false;

                if (array_key_exists('tab', $child['config']) && $tabLinksEmpty && can('list', $child['config'])) {
                    $order = isset($child['config']['tab']['order']) ? $child['config']['tab']['order'] : 0;
                    $label = isset($child['config']['tab']['label']) ? $child['config']['tab']['label'] : $child['config']['plural'];

                    if (isset($siblingsEmpty)) {
                        $url = $this->fullPath."/".$key;
                    } else {
                        $url = $this->parentPath;
                        if (!is_null($this->id)) {
                            $url = str_replace($this->identifier, $key, $url);
                        } else {
                            $url.="/".$key;
                        }
                    }

                    $this->tabLinks[$label] = [
                        'url' => $url,
                        'order' => $order,
                        'current' => $current
                    ];
                }
                if (array_key_exists('menu', $child['config']) && $menuLinksEmpty && can('list', $child['config'])) {
                    $order = isset($child['config']['menu']['order']) ? $child['config']['menu']['order'] : 0;
                    $label = isset($child['config']['menu']['label']) ? $child['config']['menu']['label'] : $child['config']['plural'];

                    if (isset($siblingsEmpty)) {
                        $url = $this->fullPath."/".$key;
                    } else {
                        $url = $this->parentPath;
                        if (!is_null($this->id)) {
                            $url = str_replace($this->identifier, $key, $url);
                        } else {
                            $url.="/".$key;
                        }
                    }

                    $this->menuLinks[$label] = [
                        'url' => $url,
                        'order' => $order,
                        'current' => $current
                    ];
                }
            }
        }

        if (!empty($this->tabLinks)) {
            uasort($this->tabLinks, function ($a, $b) {
                return $a['order'] <=> $b['order'];
            });
        }

        if (!empty($this->menuLinks)) {
            uasort($this->menuLinks, function ($a, $b) {
                return $a['order'] <=> $b['order'];
            });
        }
    }

    public function _buildPillButtons()
    {
        // back button
        if (in_array($this->pageType, ["list", "view", "edit"])) {
            if (!$this->disableBack && empty($this->pillButtons["back"])) {
                if (empty($this->backButtonText)) {
                    if (isset($this->id)) {
                        $this->backButtonText = "Back to ".$this->config['plural'];
                    } else {
                        $this->backButtonText = "Back to ".$this->parentConfig['singular'];
                    }
                }

                // hide buttons if the parent is a module or if the parent is a prefix and we're on list mode
                if (($this->parentConfig['route_type'] == "module" && ($this->pageType == "view" || ($this->pageType == "edit" && !$this->disablePillsOnEdit))) ||
                    ($this->parentConfig['route_type'] != "module" && $this->parentConfig['route_type'] == "prefix" && $this->pageType == "view" && $this->config['route_type'] != "resource-page") ||
                    ($this->parentConfig['route_type'] != "module" && $this->parentConfig['route_type'] != "prefix")) {
                    if (is_null($this->backPath)) {
                        $this->backPath = $this->parentPath;
                    }

                    if (!empty($this->backPath)) {
                        $this->backButton = [
                            "label" => $this->backButtonText,
                            "path"  => $this->backPath,
                            "order" => 0,
                            "icon"  => "arrow-left",
                        ];
                    }
                }
            }
        }

        // all other pills from structure
        if ($this->pageType == "view" || ($this->pageType == "edit" && !$this->disablePillsOnEdit)) {
            if (!empty($this->structure)) {
                foreach ($this->structure as $key => $structure) {
                    if ($key != "config") {
                        if (isset($structure['config']) && isset($structure['config']['pill'])) {
                            $c = $structure['config'];

                            $splitIdentifierPath = explode(".", $structure['config']['identifier_path']);
                            $identifier = end($splitIdentifierPath);

                            if (can('list', $c)) {
                                $pill = [
                                    "label" => $c['plural'],
                                    "path"  => isset($c['pill']['path']) ? $c['pill']['path'] : $this->fullPath."/".$key, // company/1/invoices
                                    "order" => isset($c['pill']['order']) ? $c['pill']['order'] : 0,
                                    "icon"  => isset($c['pill']['icon']) ? $c['pill']['icon'] : "folder",
                                    "id" => $key
                                ];
                                if (isset($c['onclick'])  && !empty($c['onclick'])) {
                                    $pill['onclick'] = $c['onclick'];
                                }
                                if (isset($c['pill']['title'])  && !empty($c['pill']['title'])) {
                                    $pill['title'] = $c['pill']['title'];
                                }

                                if (isset($c['pill']['attributes'])  && is_array($c['pill']['attributes'])) {
                                    $pill['attributes'] = $c['pill']['attributes'];
                                }

                                if (isset($c['pill']['sub']) && $c['pill']['sub']) {
                                    $this->pillButtons['sub']['list'][] = $pill;
                                } else {
                                    $this->pillButtons[] = $pill;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($this->pillButtons)) {
            uasort($this->pillButtons, function ($a, $b) {
                return $a['order'] <=> $b['order'];
            });
        }
    }

    public function _buildHeading()
    {
        $heading="";

        if (empty($this->heading)) {
            if ($this->pageType == "list") {
                $heading = $this->config['plural'];
                if (!empty($this->parentId)) {
                    if (usingSoftDeletes($this->parentModel)) {
                        $query = $this->parentModel::withTrashed()->findOrFail($this->parentId);
                    } else {
                        $query = $this->parentModel::findOrFail($this->parentId);
                    }
                    $heading  .= " of ".$query->{$this->parentConfig['db']['column']};
                }
            } elseif ($this->pageType == "create") {
                if (!empty($this->parentId)) {
                    if (usingSoftDeletes($this->parentModel)) {
                        $query = ($this->parentModel::withTrashed()->findOrFail($this->parentId));
                    } else {
                        $query = ($this->parentModel::findOrFail($this->parentId));
                    }

                    $heading  .= " ".$query->{$this->parentConfig['db']['column']}." ".$this->config['singular'];
                } else {
                    $heading  .= " ".$this->config['singular'];
                }
            } elseif (in_array($this->pageType, ["view", "edit"]) && !empty($this->id)) {
                if (empty($this->record)) {
                    if (usingSoftDeletes($this->model)) {
                        $query = ($this->model::withTrashed()->findOrFail($this->id));
                    } else {
                        $query = ($this->model::findOrFail($this->id));
                    }
                    $heading = $query->{$this->config['db']['column']};
                } else {
                    $heading = $this->record->{$this->config['db']['column']};
                }
            } else {
                $heading = $this->config['plural'];
            }

            if (isset($this->pageTypeDescription)) {
                $heading = $this->pageTypeDescription." ".$heading;
            }

            $this->heading = $heading;
        }
    }

    public function _buildSidebar()
    {
        $sidebarLinks = [];
        $orderStart = 1000;

        $parentIdentifier = $this->parentConfig['identifier_path'];

        if (isset($this->parentConfig)) {
            $l1Identifier = $this->parentConfig['identifier_path'];
            $l2Identifier = $this->identifier;
        } else {
            $l1Identifier = $this->identifier;
        }

        $structureToUse = config("structure");
        foreach (config("structure") as $struc) {
            if ($struc['config']['route_type'] == 'module') {
                $structureToUse = $struc;
            }
        }

        foreach ($structureToUse as $l1Struc) {
            if (isset($l1Struc['config']['sidebar'])) {
                $config = $l1Struc['config'];
                $sidebar = $l1Struc['config']['sidebar'];

                $splitIdentifierPath = explode(".", $l1Struc['config']['identifier_path']);

                $extra = '';
                if (isset($sidebar['extra'])) {
                    $model = $config['db']['model'];
                    $model = "\App\\$model";
                    $m = new $model();
                    $e = $sidebar['extra'];
                    $extra = $m->$e();
                }
                $link = [
                    "label" => isset($sidebar['label']) ? $sidebar['label'] : $l1Struc['config']['plural'],
                    "order" => isset($sidebar['order']) ? $sidebar['order'] : $orderStart++,
                    "identifier_path" => $l1Struc['config']['identifier_path'],
                    "extra" => $extra
                ];

                $link['current'] = $this->identifierPath == $l1Struc['config']['identifier_path'] ? true : false;

                if (isset($sidebar['url'])) {
                    $link["url"] = $sidebar['url'];
                } elseif (!isset($sidebar['no-url'])) {
                    $link["url"] = "/".str_replace(".", "/", $config['identifier_path']);
                }

                if (!empty($sidebar['attributes'])) {
                    $link["attributes"] = $sidebar['attributes'];
                }

                if (isset($config['icon'])) {
                    $link["icon"] = $config['icon'];
                }

                $subOrderStart = 1000;
                $sidebarSubLinks = [];
                foreach ($l1Struc as $l2Struc) {
                    if (isset($l2Struc['config']['sidebar'])) {
                        $subConfig = $l2Struc['config'];
                        $subSidebar = $l2Struc['config']['sidebar'];
                        if (!empty($l2Struc['config']['identifier_path'])) {
                            $l2SplitIdentifierPath = explode(".", $l2Struc['config']['identifier_path']);
                            $subIdentifier = end($l2SplitIdentifierPath);
                        }

                        $subLink = [
                            "label" => isset($subSidebar['label']) ? $subSidebar['label'] : $l2Struc['config']['plural'],
                            "order" => isset($subSidebar['order']) ? $subSidebar['order'] : $subOrderStart
                        ];

                        $subLink['current'] = isset($l2Identifier) && isset($subIdentifier) && $l2Identifier == $subIdentifier && $splitIdentifierPath[0] == $this->parentConfig['identifier_path'] ? true : false;

                        if (isset($subSidebar['url'])) {
                            $subLink["url"] = $subSidebar['url'];
                        } else {
                            $subLink["url"] = "/".str_replace(".", "/", $subConfig['identifier_path']);
                        }

                        if (!empty($subSidebar['attributes'])) {
                            $subLink["attributes"] = $subSidebar['attributes'];
                        }

                        if (isset($subConfig['icon'])) {
                            $subLink["icon"] = $subConfig['icon'];
                        }

                        if (can('list', $subConfig)) {
                            $sidebarSubLinks[] = $subLink;
                        }
                    }
                }

                if (!empty($sidebarSubLinks)) {
                    uasort($sidebarSubLinks, function ($a, $b) {
                        return $a['order'] <=> $b['order'];
                    });

                    $link['sub_menu'] = $sidebarSubLinks;
                }

                $continue = true;
                if (isset($config['role']) && !is_null($this->user)) {
                    if (is_array($config['role'])) {
                        if (!$this->user->roles->whereIn("slug", $config['role'])->count() == 1) {
                            $continue = false;
                        }
                    } else if (!$this->user->inRole($config['role'])) {
                        $continue = false;
                    }
                }

                if (can('list', $config)) {
                    $this->sidebarLinks[] = $link;
                }
            }
        }

        if (!empty($this->sidebarLinks)) {
            uasort($this->sidebarLinks, function ($a, $b) {
                return $a['order'] <=> $b['order'];
            });
        }
    }

    public function _buildFormButtons()
    {
        // all other pills from structure
        if (in_array($this->pageType, ["edit", "create"])) {
            if (($this->pageType == "edit" && can('edit', $this->config)) || ($this->pageType == "create" && can('create', $this->config))) {
                $this->formButtons["submit"] = [
                    "label" => $this->submitButtonText,
                    "order" => 100,
                    "class" => ['button', 'is-primary', 'submitbutton'],
                ];

                $this->formButtons["cancel"] = [
                    "label" => $this->cancelButtonText,
                    "order" => 200,
                    "class" => ['button'],
                    "href" => $this->cancelPath
                ];

                $order = 1;
                if (isset($this->config['form_buttons']) || !empty($this->formButtons)) {
                    $allButtons = [];
                    if (isset($this->config['form_buttons'])) {
                        $allButtons = $this->config['form_buttons'];
                    }

                    if (!empty($this->formButtons)) {
                        $allButtons = array_merge($allButtons, $this->formButtons);
                        $this->formButtons = [];
                    }

                    foreach ($allButtons as $key => $button) {
                        if (isset($button['label']) && !empty($button['label'])) {
                            $formButton = ['label' => $button['label']];

                            if (isset($button['order']) && !empty($button['order'])) {
                                $formButton['order'] = $button['order'];
                            } else {
                                $formButton['order'] = $order;
                                $order++;
                            }

                            if (isset($button['class']) && !empty($button['class'])) {
                                if (!is_array($button['class'])) {
                                    $button['class'] = [$button['class']];
                                }

                                $formButton['class'] = $button['class'];
                            }

                            if (isset($button['onclick'])  && !empty($button['onclick'])) {
                                $formButton['onclick'] = $button['onclick'];
                            }

                            if (isset($button['href'])  && !empty($button['href'])) {
                                $formButton['href'] = $button['href'];
                            }

                            if (isset($button['name'])  && !empty($button['name'])) {
                                $formButton['name'] = $button['name'];
                            }

                            if (isset($button['value'])  && !empty($button['value'])) {
                                $formButton['value'] = $button['value'];
                            }

                            if (isset($button['icon_before'])  && !empty($button['icon_before'])) {
                                $formButton['icon_before'] = $button['icon_before'];
                            }

                            if (isset($button['icon_after'])  && !empty($button['icon_after'])) {
                                $formButton['icon_after'] = $button['icon_after'];
                            }

                            if (isset($button['id'])  && !empty($button['id'])) {
                                $formButton['id'] = $button['id'];
                            }

                            $this->formButtons[$key] = $formButton;
                        }
                    }
                }
            }
        }

        if (!empty($this->formButtons)) {
            uasort($this->formButtons, function ($a, $b) {
                return $a['order'] <=> $b['order'];
            });
        }
    }
}
