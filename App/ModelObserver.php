<?php
namespace App;

/***********************************************************************************************************************
 * Class ModelObserver
 *     public function creating($model)
 *     public function created($model)
 *     public function updating($model)
 *     public function updated($model)
 *     public function deleting($model)
 *     public function deleted($model)
 *     public function restoring($model)
 *     public function restored($model)
 *
 * Observer for Model lifecycle.
 * Methods must return true to keep Model processing ; if false is returned it will cancel processing.
 */
class ModelObserver
{
	/*******************************************************************************************************************
	 * public function creating($model)
	 *     $model : Current $model
	 *
	 * Method is called before inserting Model into database
	 */
	public function creating($model) { return true; }

	/*******************************************************************************************************************
	 * public function created($model)
	 *     $model : Current $model
	 *
	 * Method is called after inserting Model into database
	 */
	public function created($model) { return true; }

	/*******************************************************************************************************************
	 * public function updating($model)
	 *     $model : Current $model
	 *
	 * Method is called before updating Model into database
	 */
	public function updating($model) { return true; }

	/*******************************************************************************************************************
	 * public function updated($model)
	 *     $model : Current $model
	 *
	 * Method is called after updating Model into database
	 */
	public function updated($model) { return true; }

	/*******************************************************************************************************************
	 * public function deleting($model)
	 *     $model : Current $model
	 *
	 * Method is called before deleting Model from database
	 */
	public function deleting($model) { return true; }

	/*******************************************************************************************************************
	 * public function deleted($model)
	 *     $model : Current $model
	 *
	 * Method is called after deleting Model from database
	 */
	public function deleted($model) { return true; }

	/*******************************************************************************************************************
	 * public function restoring($model)
	 *     $model : Current $model
	 *
	 * Method is called before restoring Model from database (softDelete=true)
	 */
	public function restoring($model) { return true; }

	/*******************************************************************************************************************
	 * public function restoring($model)
	 *     $model : Current $model
	 *
	 * Method is called after restoring Model from database (softDelete=true)
	 */
	public function restored($model) { return true; }
}
