<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryTransactionDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateInventoryTransactionRequest;
use App\Http\Requests\UpdateInventoryTransactionRequest;
use App\Repositories\InventoryTransactionRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;

class InventoryTransactionController extends AppBaseController
{
    /** @var InventoryTransactionRepository $inventoryTransactionRepository*/
    private $inventoryTransactionRepository;

    public function __construct(InventoryTransactionRepository $inventoryTransactionRepo)
    {
        $this->inventoryTransactionRepository = $inventoryTransactionRepo;
    }

    /**
     * Display a listing of the InventoryTransaction.
     *
     * @param InventoryTransactionDataTable $inventoryTransactionDataTable
     *
     * @return Response
     */
    public function index(InventoryTransactionDataTable $inventoryTransactionDataTable)
    {
        return $inventoryTransactionDataTable->render('inventory_transactions.index');
    }

    /**
     * Show the form for creating a new InventoryTransaction.
     *
     * @return Response
     */
    public function create()
    {
        return view('inventory_transactions.create');
    }

    /**
     * Store a newly created InventoryTransaction in storage.
     *
     * @param CreateInventoryTransactionRequest $request
     *
     * @return Response
     */
    public function store(CreateInventoryTransactionRequest $request)
    {
        $input = $request->all();

        $inventoryTransaction = $this->inventoryTransactionRepository->create($input);

        Flash::success('Inventory Transaction saved successfully.');

        return redirect(route('inventoryTransactions.index'));
    }

    /**
     * Display the specified InventoryTransaction.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $inventoryTransaction = $this->inventoryTransactionRepository->find($id);

        if (empty($inventoryTransaction)) {
            Flash::error('Inventory Transaction not found');

            return redirect(route('inventoryTransactions.index'));
        }

        return view('inventory_transactions.show')->with('inventoryTransaction', $inventoryTransaction);
    }

    /**
     * Show the form for editing the specified InventoryTransaction.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $inventoryTransaction = $this->inventoryTransactionRepository->find($id);

        if (empty($inventoryTransaction)) {
            Flash::error('Inventory Transaction not found');

            return redirect(route('inventoryTransactions.index'));
        }

        return view('inventory_transactions.edit')->with('inventoryTransaction', $inventoryTransaction);
    }

    /**
     * Update the specified InventoryTransaction in storage.
     *
     * @param int $id
     * @param UpdateInventoryTransactionRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateInventoryTransactionRequest $request)
    {
        $inventoryTransaction = $this->inventoryTransactionRepository->find($id);

        if (empty($inventoryTransaction)) {
            Flash::error('Inventory Transaction not found');

            return redirect(route('inventoryTransactions.index'));
        }

        $inventoryTransaction = $this->inventoryTransactionRepository->update($request->all(), $id);

        Flash::success('Inventory Transaction updated successfully.');

        return redirect(route('inventoryTransactions.index'));
    }

    /**
     * Remove the specified InventoryTransaction from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $inventoryTransaction = $this->inventoryTransactionRepository->find($id);

        if (empty($inventoryTransaction)) {
            Flash::error('Inventory Transaction not found');

            return redirect(route('inventoryTransactions.index'));
        }

        $this->inventoryTransactionRepository->delete($id);

        Flash::success('Inventory Transaction deleted successfully.');

        return redirect(route('inventoryTransactions.index'));
    }
}
