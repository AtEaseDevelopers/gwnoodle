<?php

namespace App\Http\Controllers;

use App\DataTables\CompanyDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Repositories\CompanyRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CompanyController extends AppBaseController
{
    /** @var CompanyRepository $companyRepository*/
    private $companyRepository;

    public function __construct(CompanyRepository $companyRepo)
    {
        $this->companyRepository = $companyRepo;
    }

    /**
     * Display a listing of the Company.
     *
     * @param CompanyDataTable $companyDataTable
     *
     * @return Response
     */
    public function index(CompanyDataTable $companyDataTable)
    {
        return $companyDataTable->render('companies.index');
    }

    /**
     * Show the form for creating a new Company.
     *
     * @return Response
     */
    public function create()
    {
        return view('companies.create');
    }

    /**
     * Store a newly created Company in storage.
     *
     * @param CreateCompanyRequest $request
     *
     * @return Response
     */
    public function store(CreateCompanyRequest $request)
    {
        $input = $request->all();

        $company = $this->companyRepository->create($input);

        Flash::success(__('companies.company_saved_successfully'));

        return redirect(route('companies.index'));
    }

    /**
     * Display the specified Company.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $company = $this->companyRepository->find($id);

        if (empty($company)) {
            Flash::error(__('companies.company_not_found'));

            return redirect(route('companies.index'));
        }

        return view('companies.show')->with('company', $company);
    }

    /**
     * Show the form for editing the specified Company.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $company = $this->companyRepository->find($id);

        if (empty($company)) {
            Flash::error(__('companies.company_not_found'));

            return redirect(route('companies.index'));
        }

        return view('companies.edit')->with('company', $company);
    }

    /**
     * Update the specified Company in storage.
     *
     * @param int $id
     * @param UpdateCompanyRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateCompanyRequest $request)
    {
        $id = Crypt::decrypt($id);
        $company = $this->companyRepository->find($id);

        if (empty($company)) {
            Flash::error(__('companies.company_not_found'));

            return redirect(route('companies.index'));
        }

        $company = $this->companyRepository->update($request->all(), $id);

        Flash::success(__('companies.company_updated_successfully'));

        return redirect(route('companies.index'));
    }

    /**
     * Remove the specified Company from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $company = $this->companyRepository->find($id);

        if (empty($company)) {
            Flash::error(__('companies.company_not_found'));

            return redirect(route('companies.index'));
        }

        $this->companyRepository->delete($id);

        Flash::success(__('companies.company_deleted_successfully'));

        return redirect(route('companies.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $company = $this->companyRepository->delete($id);

            $count = $count + Company::destroy($id);
        }

        return $count;
    }
}
