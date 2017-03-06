<?php

namespace Core\Services\Contracts;

interface ResourceController
{
    /**
     * Lists all articles.
     *
     * @return string
     */
    public function index();

    /**
     * Shows the form to create a new article.
     *
     * @return string
     */
    public function create();

    /**
     * Stores a new article.
     *
     * @return string
     */
    public function store();

    /**
     * Deletes an article.
     *
     * @param int $id
     * @return string
     */
    public function destroy($id);

    /**
     * Shows the edit view and gathers the old data.
     *
     * @param int $id
     * @return string
     */
    public function edit($id);

//    /**
//     * Clones the given model and shows the edit view.
//     *
//     * @param int $id id
//     * @return string
//     */
//    public function replicate($id);

    /**
     * Stores an article to the database.
     *
     * @param int $id id of the article to store
     * @return string
     */
    public function update($id);

    /**
     * Shows a single article.
     *
     * @param int $id
     * @return string
     */
    public function show($id);
}