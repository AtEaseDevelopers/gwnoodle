<?php

namespace App\Http\Controllers;

use App\DataTables\BonusDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateBonusRequest;
use App\Http\Requests\UpdateBonusRequest;
use App\Repositories\BonusRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use App\Models\Bonus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BonusController extends AppBaseController
{
    /** @var BonusRepository $bonusRepository*/
    private $bonusRepository;

    public function __construct(BonusRepository $bonusRepo)
    {
        $this->bonusRepository = $bonusRepo;
    }

    /**
     * Display a listing of the Bonus.
     *
     * @param BonusDataTable $bonusDataTable
     *
     * @return Response
     */
    public function index(BonusDataTable $bonusDataTable)
    {
        return $bonusDataTable->render('bonuses.index');
    }

    /**
     * Show the form for creating a new Bonus.
     *
     * @return Response
     */
    public function create()
    {
        return view('bonuses.create');
    }

    /**
     * Store a newly created Bonus in storage.
     *
     * @param CreateBonusRequest $request
     *
     * @return Response
     */
    public function store(CreateBonusRequest $request)
    {
        $input = $request->all();

        $input['bonusstart'] = date_create($input['bonusstart']);
        $input['bonusend'] = date_create($input['bonusend']);
        $bonus = $this->bonusRepository->create($input);

        Flash::success('Bonus saved successfully.');

        return redirect(route('bonuses.index'));
    }

    /**
     * Display the specified Bonus.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $bonus = $this->bonusRepository->find($id);

        if (empty($bonus)) {
            Flash::error('Bonus not found');

            return redirect(route('bonuses.index'));
        }

        return view('bonuses.show')->with('bonus', $bonus);
    }

    /**
     * Show the form for editing the specified Bonus.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $bonus = $this->bonusRepository->find($id);

        if (empty($bonus)) {
            Flash::error('Bonus not found');

            return redirect(route('bonuses.index'));
        }

        return view('bonuses.edit')->with('bonus', $bonus);
    }

    /**
     * Update the specified Bonus in storage.
     *
     * @param int $id
     * @param UpdateBonusRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateBonusRequest $request)
    {
        $id = Crypt::decrypt($id);
        $bonus = $this->bonusRepository->find($id);

        if (empty($bonus)) {
            Flash::error('Bonus not found');

            return redirect(route('bonuses.index'));
        }

        $input = $request->all();

        $input['bonusstart'] = date_create($input['bonusstart']);
        $input['bonusend'] = date_create($input['bonusend']);
        $bonus = $this->bonusRepository->update($input, $id);

        Flash::success('Bonus updated successfully.');

        return redirect(route('bonuses.index'));
    }

    /**
     * Remove the specified Bonus from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $bonus = $this->bonusRepository->find($id);

        if (empty($bonus)) {
            Flash::error('Bonus not found');

            return redirect(route('bonuses.index'));
        }

        $this->bonusRepository->delete($id);

        Flash::success('Bonus deleted successfully.');

        return redirect(route('bonuses.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewBonus('.Auth::id().',\'Bonuses\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = Bonus::destroy($ids);

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = Bonus::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
}
