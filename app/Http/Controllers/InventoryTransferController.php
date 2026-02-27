<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryTransferDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateInventoryTransferRequest;
use App\Http\Requests\UpdateInventoryTransferRequest;
use App\Repositories\InventoryTransferRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;

class InventoryTransferController extends AppBaseController
{
    /** @var InventoryTransferRepository $inventoryTransferRepository*/
    private $inventoryTransferRepository;

    public function __construct(InventoryTransferRepository $inventoryTransferRepo)
    {
        $this->inventoryTransferRepository = $inventoryTransferRepo;
    }

    /**
     * Display a listing of the InventoryTransfer.
     *
     * @param InventoryTransferDataTable $inventoryTransferDataTable
     *
     * @return Response
     */
    public function index(InventoryTransferDataTable $inventoryTransferDataTable)
    {
        return $inventoryTransferDataTable->render('inventory_transfers.index');
    }

    /**
     * Show the form for creating a new InventoryTransfer.
     *
     * @return Response
     */
    public function create()
    {
        return view('inventory_transfers.create');
    }

    /**
     * Store a newly created InventoryTransfer in storage.
     *
     * @param CreateInventoryTransferRequest $request
     *
     * @return Response
     */
    public function store(CreateInventoryTransferRequest $request)
    {
        $input = $request->all();

        $inventoryTransfer = $this->inventoryTransferRepository->create($input);

        Flash::success('Inventory Transfer saved successfully.');

        return redirect(route('inventoryTransfers.index'));
    }

    /**
     * Display the specified InventoryTransfer.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $inventoryTransfer = $this->inventoryTransferRepository->find($id);

        if (empty($inventoryTransfer)) {
            Flash::error('Inventory Transfer not found');

            return redirect(route('inventoryTransfers.index'));
        }

        return view('inventory_transfers.show')->with('inventoryTransfer', $inventoryTransfer);
    }

    /**
     * Show the form for editing the specified InventoryTransfer.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $inventoryTransfer = $this->inventoryTransferRepository->find($id);

        if (empty($inventoryTransfer)) {
            Flash::error('Inventory Transfer not found');

            return redirect(route('inventoryTransfers.index'));
        }

        return view('inventory_transfers.edit')->with('inventoryTransfer', $inventoryTransfer);
    }

    /**
     * Update the specified InventoryTransfer in storage.
     *
     * @param int $id
     * @param UpdateInventoryTransferRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateInventoryTransferRequest $request)
    {
        $inventoryTransfer = $this->inventoryTransferRepository->find($id);

        if (empty($inventoryTransfer)) {
            Flash::error('Inventory Transfer not found');

            return redirect(route('inventoryTransfers.index'));
        }

        $inventoryTransfer = $this->inventoryTransferRepository->update($request->all(), $id);

        Flash::success('Inventory Transfer updated successfully.');

        return redirect(route('inventoryTransfers.index'));
    }

    /**
     * Remove the specified InventoryTransfer from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $inventoryTransfer = $this->inventoryTransferRepository->find($id);

        if (empty($inventoryTransfer)) {
            Flash::error('Inventory Transfer not found');

            return redirect(route('inventoryTransfers.index'));
        }

        $this->inventoryTransferRepository->delete($id);

        Flash::success('Inventory Transfer deleted successfully.');

        return redirect(route('inventoryTransfers.index'));
    }
}
