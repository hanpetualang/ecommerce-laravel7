<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Kategori;
use App\Pelanggan;
use App\Pesanan;
use App\Produk;
use Carbon\Carbon;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PesananImport;

class PesananController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['pesanan'] = Pesanan::orderBy('id', 'DESC')->paginate(5);
        return view('backend.pesanan.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['kategori'] = Kategori::all();
        $data['produk'] = Produk::where('status', '=', 'Rilis')->get();
        $data['pelanggan'] = Pelanggan::all();
        return view('backend.pesanan.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // check if all field is set
        if(empty($request->produk_id) || empty($request->pelanggan_id) || empty($request->qty) || empty($request->status) || empty($request->date)) {
            return redirect()->back()->withInput()->with('error', 'Semua field harus diisi!');
        }
        $today = Carbon::now('GMT+7');
        $invoice = $today->year . '/' . $today->month . '/' . $today->day . '/' . random_int(1000, 9999);
        $product = Produk::find($request->produk_id);
        $total_harga = $request->qty * $product->harga;
        Pesanan::create([
            'produk_id' => $request->produk_id,
            'pelanggan_id' => $request->pelanggan_id,
            'invoice_id' => $invoice,
            'qty' => $request->qty,
            'total_harga' => $total_harga,
            'status' => $request->status,
            'date' => $request->date
        ]);
        return redirect()->route('pesanan.index')->with('success', 'Pesanan berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data['pesanan'] = Pesanan::find($id);
        return view('backend.pesanan.detail', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data['pesanan'] = Pesanan::find($id);
        $data['kategori'] = Kategori::all();
        $data['produk'] = Produk::where('status', '=', 'Rilis')->get();
        $data['pelanggan'] = Pelanggan::all();
        return view('backend.pesanan.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $pesanan = Pesanan::find($id);
        $product = Produk::find($pesanan->produk_id);
        $total_harga = $request->qty * $product->harga;
        $pesanan->update(array_merge($request->all(), [
            'pelanggan_id' => $request->pelanggan_id,
            'total_harga' => $total_harga,
        ]));
        return redirect()->route('pesanan.index')->with('success', 'Pesanan berhasil diubah!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(Pesanan::destroy($id))
            return redirect()->back()->with('success', 'Data berhasil dihapus');
        else
            return redirect()->back()->with('error', 'Data gagal dihapus');
    }

    public function search(Request $request)
    {
        $pelanggan_id = Pelanggan::where('name', 'like', '%' . $request->search . '%')->first()->id  ?? null;
        $data['pesanan'] = Pesanan::where('pelanggan_id', '=', $pelanggan_id)->paginate(5);
        return view('backend.pesanan.index', $data);
    }
    public function filter(Request $request)
    {
        $data['pesanan'] = Pesanan::where('date', '>=', $request->start)->where('date', '<=', $request->end)->paginate(5);
        return view('backend.pesanan.index', $data);
    }

    public function export(){
        $data['pesanan'] = Pesanan::all();
        $pdf = PDF::loadView('backend.pesanan.export', $data);
        return $pdf->download('pesanan.pdf');
    }
    public function import(Request $request){
        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv,txt'
        ]);
        Excel::import(new PesananImport, $request->file('file'));
        return redirect()->route('pesanan.index')->with('success', 'Data berhasil diimport');
    }
}
