<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArchivosFlujo;
use App\Models\Flujos;
use App\Models\RestriccionEmpresa;
use App\Models\UsuarioRestriccionEmpresa;
use App\Models\UsuarioRestriccionTexto;

class ArchivosFlujoController extends Controller
{
    public function index()
    {
        $archivosflujo = ArchivosFlujo::join('usuarios', function($join){
            $join->on('usuarios.id_usuario',
                '=',
                'ArchivoFlujo.id_usuario'
            );
        })
        ->selectRaw(
            "ArchivoFlujo.id_archivoflujo,
             ArchivoFlujo.id_flujo,
             ArchivoFlujo.id_usuario,
             usuarios.nombre_usuario,
             ArchivoFlujo.descripcion,
             ArchivoFlujo.archivo,
             ArchivoFlujo.archivo_original,
             ArchivoFlujo.activo,
             ArchivoFlujo.eliminado"
        )
        ->where('ArchivoFlujo.activo', 1)
        ->where('ArchivoFlujo.eliminado', 0)
        ->orderBy('ArchivoFlujo.id_archivoflujo', 'ASC')
        ->get();
        $datos = array();
        $datos['archivos'] = $archivosflujo;
        return $datos;
    }

    public function show($id_usuario, $id_flujo)
    {
        $archivosflujo = ArchivosFlujo::join('usuarios', function($join){
            $join->on('usuarios.id_usuario',
                '=',
                'ArchivoFlujo.id_usuario'
            );
        })
        ->selectRaw(
            "ArchivoFlujo.id_archivoflujo,
             ArchivoFlujo.id_flujo,
             ArchivoFlujo.id_usuario,
             usuarios.nombre_usuario,
             ArchivoFlujo.descripcion,
             ArchivoFlujo.archivo,
             ArchivoFlujo.archivo_original,
             ArchivoFlujo.activo,
             ArchivoFlujo.eliminado"
        )
        ->where('ArchivoFlujo.id_usuario', $id_usuario)
        ->orWhere('ArchivoFlujo.id_flujo', $id_flujo)
        ->where('ArchivoFlujo.activo', 1)
        ->where('ArchivoFlujo.eliminado', 0)
        ->orderBy('ArchivoFlujo.id_archivoflujo', 'ASC')
        ->get();
        $datos = array();
        $datos['archivos'] = $archivosflujo;
        return $datos;
    }

    public function store(Request $request)
    {
        $bandera = 1;
        foreach($request->archivos as $archivo){
            $partesArchivo = explode("|",$archivo);
            $archivosflujo = new ArchivosFlujo;
            $archivosflujo->id_flujo = $request->id_flujo;
            $archivosflujo->id_usuario = $request->id_usuario;
            $archivosflujo->descripcion = $request->descripcion;
            $archivosflujo->archivo = $request->url.$partesArchivo[0];
            if($partesArchivo[1]){
                $archivosflujo->archivo_original = $request->url.$partesArchivo[1];
            }
            $archivosflujo->activo = 1;
            $archivosflujo->eliminado = 0;
            $archivosflujo->save();
            $bandera*=1;
        }

        if($bandera==1){
           /*  Flujos::where('id_flujo', $request->id_flujo)
            ->update(['estado' => 2, 'nivel' => 0]); */
            return response()->json("OK");
        }else{
            return response()->json("Error");
        }
    }

    public function update(Request $request, $id, $opcion)
    {
        if ($opcion == '1') {
            $archivosflujo = ArchivosFlujo::find($id);
            $archivosflujo->eliminado = 1;
            $archivosflujo->activo = 0;
            $archivosflujo->save();
            return response()->json("OK"); 
        }
        if ($opcion == '2') {
            $archivosflujo = ArchivosFlujo::find($id);
            $archivosflujo->archivo = $request->url.$request->nombre_archivo;
            $archivosflujo->save();
            return response()->json("OK"); 
        }
    }

    public function delete(Request $request)
    {
        $archivosflujo->delete();

        return response()->json(null, 204);
    }

    public function flujosconarchivos($id_usuario){
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$id_usuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$id_usuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $archivosflujo = ArchivosFlujo::selectRaw(
            "ArchivoFlujo.id_flujo"
        )
        ->where('ArchivoFlujo.id_usuario', $id_usuario)
        ->orderBy('ArchivoFlujo.id_archivoflujo', 'ASC')
        ->get();
        $ListaFlujosEstado = Flujos::selectRaw(
            "Flujo.id_flujo,
            Flujo.id_tipoflujo,
            Flujo.doc_num,
            Flujo.tipo,
            DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d')as tax_date,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
            Flujo.comments,
            Flujo.activo,
            Flujo.estado,
            Flujo.nivel,
            Flujo.id_grupoautorizacion,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
               WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
               WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
               WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque"
        )
        ->whereIn('Flujo.id_flujo', $archivosflujo)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();
        $datos = array();
        $datos['flujos'] = $ListaFlujosEstado;
        return $datos;  
    }
}
