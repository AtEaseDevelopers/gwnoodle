<?php

namespace App\Http\Controllers;

use App\DataTables\ReportdetailDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateReportdetailRequest;
use App\Http\Requests\UpdateReportdetailRequest;
use App\Repositories\ReportdetailRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;

class ReportdetailController extends AppBaseController
{
    /** @var ReportdetailRepository $reportdetailRepository*/
    private $reportdetailRepository;

    public function __construct(ReportdetailRepository $reportdetailRepo)
    {
        $this->reportdetailRepository = $reportdetailRepo;
    }

    /**
     * Display a listing of the Reportdetail.
     *
     * @param ReportdetailDataTable $reportdetailDataTable
     *
     * @return Response
     */
    public function index(ReportdetailDataTable $reportdetailDataTable)
    {
        return $reportdetailDataTable->render('reportdetails.index');
    }

    /**
     * Show the form for creating a new Reportdetail.
     *
     * @return Response
     */
    public function create()
    {
        return view('reportdetails.create');
    }

    /**
     * Store a newly created Reportdetail in storage.
     *
     * @param CreateReportdetailRequest $request
     *
     * @return Response
     */
    public function store(CreateReportdetailRequest $request)
    {
        $input = $request->all();

        $reportdetail = $this->reportdetailRepository->create($input);

        Flash::success('Reportdetail saved successfully.');

        return redirect(route('reportdetails.index'));
    }

    /**
     * Display the specified Reportdetail.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $reportdetail = $this->reportdetailRepository->find($id);

        if (empty($reportdetail)) {
            Flash::error('Reportdetail not found');

            return redirect(route('reportdetails.index'));
        }

        return view('reportdetails.show')->with('reportdetail', $reportdetail);
    }

    /**
     * Show the form for editing the specified Reportdetail.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $reportdetail = $this->reportdetailRepository->find($id);

        if (empty($reportdetail)) {
            Flash::error('Reportdetail not found');

            return redirect(route('reportdetails.index'));
        }

        return view('reportdetails.edit')->with('reportdetail', $reportdetail);
    }

    /**
     * Update the specified Reportdetail in storage.
     *
     * @param int $id
     * @param UpdateReportdetailRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateReportdetailRequest $request)
    {
        $reportdetail = $this->reportdetailRepository->find($id);

        if (empty($reportdetail)) {
            Flash::error('Reportdetail not found');

            return redirect(route('reportdetails.index'));
        }

        $reportdetail = $this->reportdetailRepository->update($request->all(), $id);

        Flash::success('Reportdetail updated successfully.');

        return redirect(route('reportdetails.index'));
    }

    /**
     * Remove the specified Reportdetail from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $reportdetail = $this->reportdetailRepository->find($id);

        if (empty($reportdetail)) {
            Flash::error('Reportdetail not found');

            return redirect(route('reportdetails.index'));
        }

        $this->reportdetailRepository->delete($id);

        Flash::success('Reportdetail deleted successfully.');

        return redirect(route('reportdetails.index'));
    }
}
