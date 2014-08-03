<?php

class UploadsController extends \BaseController {

	protected $validation_error_message = 'Validation Error.';
	protected $access_denied_message = 'Access denied.';
	protected $created_message = 'Record created.';
	protected $deleted_message = 'Record deleted.';
	protected $delete_error_message = 'Error deleting record.';

	/**
	 * Store a newly created upload in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		Upload::setRules('store');
		if(!Upload::canCreate())
		{
			return $this->_access_denied();
		}

		$file = Input::file('file');
		$hash = md5(microtime().time());
		$data = [];
		$data['path'] = public_path() . '/uploads/' . $hash . '/';
		mkdir($data['path']);
		$data['url'] =  url('uploads/' . $hash);
		$data['name'] = $file->getClientOriginalName();
		$data['type'] = $file->getMimeType();
		$data['size'] = $file->getSize();
		$data['uploadable_type'] = Request::header('X-Uploader-Class');
		$data['uploadable_id'] = Request::header('X-Uploader-Id');
		$file->move($data['path'], $data['name']);
		$upload = Upload::create($data);
		if(!$upload->save())
		{
			return $this->_validation_error($upload);
		}
		if(Request::ajax())
		{
			return Response::json($upload, 201);
		}
		return Redirect::back()
			->with('notification:success', $this->created_message);
	}

	/**
	 * Remove the specified upload from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		$upload = Upload::findOrFail($id);
		if(!$upload->canDelete())
		{
			return $this->_access_denied();
		}
		File::deleteDirectory($upload->path);
		$upload->delete();
		if(Request::ajax())
		{
			return Response::json($this->deleted_message);
		}
		return Redirect::back()
			->with('notification:success', $this->deleted_message);
	}

	/**
	 * Response Shorthands
	 */

	public function _access_denied()
	{
		if(Request::ajax())
		{
			return Response::json($this->access_denied_message, 403);
		}
		return Redirect::back()
			->with('notification:danger', $this->access_denied_message);
	}

	public function _validation_error($upload)
	{
		if(Request::ajax())
		{
			return Response::json($upload->validationErrors, 400);
		}
		return Redirect::back()
			->withErrors($upload->validationErrors)
			->withInput()
			->with('notification:danger', $this->validation_error_message);
	}

	/**
	 * Custom Methods. Dont forget to add these to routes: Route::get('example/name', 'ExampleController@getName');
	 */
	
	// public function getName()
	// {
	// }

	/**
	 * Constructor
	 */

	public function __construct()
	{
		parent::__construct();
		View::share('controller', 'Upload');
	}

}
