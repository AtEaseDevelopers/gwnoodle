<?php

namespace App\Http\Controllers;

use App\DataTables\SpecialPriceDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateSpecialPriceRequest;
use App\Http\Requests\UpdateSpecialPriceRequest;
use App\Repositories\SpecialPriceRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\SpecialPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SpecialPriceController extends AppBaseController
{
    /** @var SpecialPriceRepository $specialPriceRepository*/
    private $specialPriceRepository;

    public function __construct(SpecialPriceRepository $specialPriceRepo)
    {
        $this->specialPriceRepository = $specialPriceRepo;
    }

    /**
     * Display a listing of the SpecialPrice.
     *
     * @param SpecialPriceDataTable $specialPriceDataTable
     *
     * @return Response
     */
    public function index(SpecialPriceDataTable $specialPriceDataTable)
    {
        return $specialPriceDataTable->render('special_prices.index');
    }

    /**
     * Show the form for creating a new SpecialPrice.
     *
     * @return Response
     */
    public function create()
    {
        return view('special_prices.create');
    }

    /**
     * Store a newly created SpecialPrice in storage.
     *
     * @param CreateSpecialPriceRequest $request
     *
     * @return Response
     */
    public function store(CreateSpecialPriceRequest $request)
    {
        $input = $request->all();

        $specialPrice = $this->specialPriceRepository->create($input);
        Flash::success(__('special_prices.special_price_saved_successfully'));

        return redirect(route('specialPrices.index'));
    }

    /**
     * Display the specified SpecialPrice.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $specialPrice = $this->specialPriceRepository->find($id);

        if (empty($specialPrice)) {
            Flash::error(__('special_prices.special_price_not_found'));

            return redirect(route('specialPrices.index'));
        }

        return view('special_prices.show')->with('specialPrice', $specialPrice);
    }

    /**
     * Show the form for editing the specified SpecialPrice.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $specialPrice = $this->specialPriceRepository->find($id);

        if (empty($specialPrice)) {
            Flash::error(__('special_prices.special_price_not_found'));

            return redirect(route('specialPrices.index'));
        }

        return view('special_prices.edit')->with('specialPrice', $specialPrice);
    }

    /**
     * Update the specified SpecialPrice in storage.
     *
     * @param int $id
     * @param UpdateSpecialPriceRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateSpecialPriceRequest $request)
    {
        $id = Crypt::decrypt($id);
        $specialPrice = $this->specialPriceRepository->find($id);

        if (empty($specialPrice)) {
            Flash::error(__('special_prices.special_price_not_found'));

            return redirect(route('specialPrices.index'));
        }

        $input = $request->all();

        $specialPrice = $this->specialPriceRepository->update($input, $id);

        Flash::success(__('special_prices.special_price_updated_successfully'));

        return redirect(route('specialPrices.index'));
    }

    /**
     * Remove the specified SpecialPrice from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $specialPrice = $this->specialPriceRepository->find($id);

        if (empty($specialPrice)) {
            Flash::error(__('special_prices.special_price_not_found'));

            return redirect(route('specialPrices.index'));
        }

        $this->specialPriceRepository->delete($id);

        Flash::success(__('special_prices.special_price_deleted_successfully'));

        return redirect(route('specialPrices.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = 0;
    
        foreach ($ids as $id) {
            
            $specialPrice = $this->specialPriceRepository->find($id);
    
            $count = $count + SpecialPrice::destroy($id);
        }
    
        return $count;
    }
    
    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = SpecialPrice::whereIn('id',$ids)->update(['status'=>$status]);
    
        return $count;
    }
}
