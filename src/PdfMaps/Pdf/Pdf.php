<?php
namespace PdfMaps\Pdf;

use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\FpdiPdfParser\CompressedReader;
use tecnickcom\tcpdf\TCPDF_STATIC;
use TcpdfOC\Tcpdf_OC;
use TcpdfOC\Tcpdf_OCG;
use TcpdfOC\Tcpdf_OCMD;

//
// this class contains new methods and changes to tcpdf and/or fpdi
//

class Pdf extends Fpdi {

    protected $openMarkedContent = 0;

    // ** NEW methods for tcpdf***************************************************

    public function defineOC ($name, $options = array()) {
        if (isset( $options['type']) AND $options['type'] == 'OCMD') {
            return new Tcpdf_OCMD($name, $options);
        } else {
            // defaults to OCG
            return new Tcpdf_OCG($name, $options);
        }
    }

    public function defineOCG ($name, $options = array()) {
        return new Tcpdf_OCG($name, $options);
    }

    public function defineOCMD ($name, $options = array()) {
        return new Tcpdf_OCMD($name, $options);
    }

    public function startOptionalContent($name) {
        $id = Tcpdf_OC::getResourceId($name);  // this could be ocg or ocmd**********************
        if ($id) {
            $this->_out('/OC /'.$id.' BDC');
            $this->openMarkedContent++;
            return TRUE;
        } else {
            echo "<br/>".Tcpdf_OC::getLastError();
            return FALSE;
        }
    }

    public function endOptionalContent() {
        if ($this->openMarkedContent > 0) {
            // close existing open marked-content layer
            $this->_out('EMC');
            $this->openMarkedContent--;
        }
    }

    protected function _putOCPropertiesInCatalog() {
        return Tcpdf_OCG::getOCProperties();
    }

    protected function _putOptionalContentInResourceDict() {
        $out = "";
        // layer portion of _putresourcedict
        $ocg = Tcpdf_OCG::getResourceDictionaryEntries();
        $ocmd = Tcpdf_OCMD::getResourceDictionaryEntries();
        if ( $ocg !== '' OR $ocmd !== '') {
            $out = ' /Properties << '.$ocg.' '.$ocmd.' >> ';
        }
        return $out;
    }

  // ** REPLACED methods *******************************************************

    protected function _putocg() {
        /** REMOVED CODE ******************************************************
	    if (empty($this->pdflayers)) {
		    return;
	    }
	    foreach ($this->pdflayers as $key => $layer) {
		     $this->pdflayers[$key]['objid'] = $this->_newobj();
		     $out = '<< /Type /OCG';
		     $out .= ' /Name '.$this->_textstring($layer['name'], $this->pdflayers[$key]['objid']);
		     $out .= ' /Usage <<';
		     if (isset($layer['print']) AND ($layer['print'] !== NULL)) {
			    $out .= ' /Print <</PrintState /'.($layer['print']?'ON':'OFF').'>>';
		     }
		     $out .= ' /View <</ViewState /'.($layer['view']?'ON':'OFF').'>>';
		     $out .= ' >> >>';
		     $out .= "\n".'endobj';
		     $this->_out($out);
	    }
        ** END REMOVED CODE **************************************************/

        foreach (array('TcpdfOC\Tcpdf_OCG', 'TcpdfOC\Tcpdf_OCMD') as $className) {
            if ( !empty($className::$items) ) {
                foreach ($className::$items as $name => $obj) {
                    $obj->setObjId( $this->_newobj() );
                    #$out .= $obj->get('objnum')." 0 obj ";
                    #echo "<br/>$out";
                    $out = $obj->getObjDefinitionString();
                    $out .= ' endobj'."\n";
                    $this->_out($out);
                }
            }
        }
        return;
    }

  // ** CHANGED methods ********************************************************

	/**
	 * Output Resources Dictionary.
	 * @protected
	 */
	protected function _putresourcedict() {
		$out = $this->_getobj(2)."\n";
		$out .= '<< /ProcSet [/PDF /Text /ImageB /ImageC /ImageI]';
		$out .= ' /Font <<';
		foreach ($this->fontkeys as $fontkey) {
			$font = $this->getFontBuffer($fontkey);
			$out .= ' /F'.$font['i'].' '.$font['n'].' 0 R';
		}
		$out .= ' >>';
		$out .= ' /XObject <<';
		$out .= $this->_getxobjectdict();
		$out .= ' >>';

		// layers
		/** START REMOVED CODE ************************************************
		if (!empty($this->pdflayers)) {
			$out .= ' /Properties <<';
			foreach ($this->pdflayers as $layer) {
				$out .= ' /'.$layer['layer'].' '.$layer['objid'].' 0 R';
			}
			$out .= ' >>';
		}
		*** END REMOVED CODE *************************************************/
        //* START ADDED CODE **************************************************

        $out .= $this->_putOptionalContentInResourceDict();

        //* END ADDED CODE ****************************************************

		if (!$this->pdfa_mode) {
			// transparency
			if (isset($this->extgstates) AND !empty($this->extgstates)) {
				$out .= ' /ExtGState <<';
				foreach ($this->extgstates as $k => $extgstate) {
					if (isset($extgstate['name'])) {
						$out .= ' /'.$extgstate['name'];
					} else {
						$out .= ' /GS'.$k;
					}
					$out .= ' '.$extgstate['n'].' 0 R';
				}
				$out .= ' >>';
			}
			if (isset($this->gradients) AND !empty($this->gradients)) {
				$gp = '';
				$gs = '';
				foreach ($this->gradients as $id => $grad) {
					// gradient patterns
					$gp .= ' /p'.$id.' '.$grad['pattern'].' 0 R';
					// gradient shadings
					$gs .= ' /Sh'.$id.' '.$grad['id'].' 0 R';
				}
				$out .= ' /Pattern <<'.$gp.' >>';
				$out .= ' /Shading <<'.$gs.' >>';
			}
		}
		// spot colors
		if (isset($this->spot_colors) AND !empty($this->spot_colors)) {
			$out .= ' /ColorSpace <<';
			foreach ($this->spot_colors as $color) {
				$out .= ' /CS'.$color['i'].' '.$color['n'].' 0 R';
			}
			$out .= ' >>';
		}
		$out .= ' >>';
		$out .= "\n".'endobj';
		$this->_out($out);
	}

// *********************************

	/**
	 * Output Catalog.
	 * @return int object id
	 * @protected
	 */
	protected function _putcatalog() {
		// put XMP
		$xmpobj = $this->_putXMP();
		// if required, add standard sRGB_IEC61966-2.1 blackscaled ICC colour profile
		if ($this->pdfa_mode OR $this->force_srgb) {
			$iccobj = $this->_newobj();
			$icc = file_get_contents(dirname(__FILE__).'/include/sRGB.icc');
			$filter = '';
			if ($this->compress) {
				$filter = ' /Filter /FlateDecode';
				$icc = gzcompress($icc);
			}
			$icc = $this->_getrawstream($icc);
			$this->_out('<</N 3 '.$filter.'/Length '.strlen($icc).'>> stream'."\n".$icc."\n".'endstream'."\n".'endobj');
		}
		// start catalog
		$oid = $this->_newobj();
		$out = '<< /Type /Catalog';
		$out .= ' /Version /'.$this->PDFVersion;
		//$out .= ' /Extensions <<>>';
		$out .= ' /Pages 1 0 R';
		//$out .= ' /PageLabels ' //...;
		$out .= ' /Names <<';
		if ((!$this->pdfa_mode) AND !empty($this->n_js)) {
			$out .= ' /JavaScript '.$this->n_js;
		}
		if (!empty($this->efnames)) {
			$out .= ' /EmbeddedFiles <</Names [';
			foreach ($this->efnames AS $fn => $fref) {
				$out .= ' '.$this->_datastring($fn).' '.$fref;
			}
			$out .= ' ]>>';
		}
		$out .= ' >>';
		if (!empty($this->dests)) {
			$out .= ' /Dests '.($this->n_dests).' 0 R';
		}
		$out .= $this->_putviewerpreferences();

		if (isset($this->LayoutMode) AND (!\TCPDF_STATIC::empty_string($this->LayoutMode))) {
			$out .= ' /PageLayout /'.$this->LayoutMode;
		}
		if (isset($this->PageMode) AND (!\TCPDF_STATIC::empty_string($this->PageMode))) {
			$out .= ' /PageMode /'.$this->PageMode;
		}
		if (count($this->outlines) > 0) {
			$out .= ' /Outlines '.$this->OutlineRoot.' 0 R';
			$out .= ' /PageMode /UseOutlines';
		}
		//$out .= ' /Threads []';
		if ($this->ZoomMode == 'fullpage') {
			$out .= ' /OpenAction ['.$this->page_obj_id[1].' 0 R /Fit]';
		} elseif ($this->ZoomMode == 'fullwidth') {
			$out .= ' /OpenAction ['.$this->page_obj_id[1].' 0 R /FitH null]';
		} elseif ($this->ZoomMode == 'real') {
			$out .= ' /OpenAction ['.$this->page_obj_id[1].' 0 R /XYZ null null 1]';
		} elseif (!is_string($this->ZoomMode)) {
			$out .= sprintf(' /OpenAction ['.$this->page_obj_id[1].' 0 R /XYZ null null %F]', ($this->ZoomMode / 100));
		}
		//$out .= ' /AA <<>>';
		//$out .= ' /URI <<>>';
		$out .= ' /Metadata '.$xmpobj.' 0 R';
		//$out .= ' /StructTreeRoot <<>>';
		//$out .= ' /MarkInfo <<>>';
		if (isset($this->l['a_meta_language'])) {
			$out .= ' /Lang '.$this->_textstring($this->l['a_meta_language'], $oid);
		}
		//$out .= ' /SpiderInfo <<>>';
		// set OutputIntent to sRGB IEC61966-2.1 if required
		if ($this->pdfa_mode OR $this->force_srgb) {
			$out .= ' /OutputIntents [<<';
			$out .= ' /Type /OutputIntent';
			$out .= ' /S /GTS_PDFA1';
			$out .= ' /OutputCondition '.$this->_textstring('sRGB IEC61966-2.1', $oid);
			$out .= ' /OutputConditionIdentifier '.$this->_textstring('sRGB IEC61966-2.1', $oid);
			$out .= ' /RegistryName '.$this->_textstring('http://www.color.org', $oid);
			$out .= ' /Info '.$this->_textstring('sRGB IEC61966-2.1', $oid);
			$out .= ' /DestOutputProfile '.$iccobj.' 0 R';
			$out .= ' >>]';
		}
		//$out .= ' /PieceInfo <<>>';

		/** START REMOVED CODE ************************************************
		if (!empty($this->pdflayers)) {
			$lyrobjs = '';
			$lyrobjs_off = '';
			$lyrobjs_lock = '';
			foreach ($this->pdflayers as $layer) {
				$layer_obj_ref = ' '.$layer['objid'].' 0 R';
				$lyrobjs .= $layer_obj_ref;
				if ($layer['view'] === false) {
					$lyrobjs_off .= $layer_obj_ref;
				}
				if ($layer['lock']) {
					$lyrobjs_lock .= $layer_obj_ref;
				}
			}
			$out .= ' /OCProperties << /OCGs ['.$lyrobjs.']';
			$out .= ' /D <<';
			$out .= ' /Name '.$this->_textstring('Layers', $oid);
			$out .= ' /Creator '.$this->_textstring('TCPDF', $oid);
			$out .= ' /BaseState /ON';
			$out .= ' /OFF ['.$lyrobjs_off.']';
			$out .= ' /Locked ['.$lyrobjs_lock.']';
			$out .= ' /Intent /View';
			$out .= ' /AS [';
			$out .= ' << /Event /Print /OCGs ['.$lyrobjs.'] /Category [/Print] >>';
			$out .= ' << /Event /View /OCGs ['.$lyrobjs.'] /Category [/View] >>';
			$out .= ' ]';
			$out .= ' /Order ['.$lyrobjs.']';
			$out .= ' /ListMode /AllPages';
			//$out .= ' /RBGroups ['..']';
			//$out .= ' /Locked ['..']';
			$out .= ' >>';
			$out .= ' >>';
		}
		*** END REMOVED CODE *************************************************/
        //* START ADDED CODE **************************************************

        $out .= $this->_putOCPropertiesInCatalog();

        //* END ADDED CODE ****************************************************

		// AcroForm
		if (!empty($this->form_obj_id)
			OR ($this->sign AND isset($this->signature_data['cert_type']))
			OR !empty($this->empty_signature_appearance)) {
			$out .= ' /AcroForm <<';
			$objrefs = '';
			if ($this->sign AND isset($this->signature_data['cert_type'])) {
				// set reference for signature object
				$objrefs .= $this->sig_obj_id.' 0 R';
			}
			if (!empty($this->empty_signature_appearance)) {
				foreach ($this->empty_signature_appearance as $esa) {
					// set reference for empty signature objects
					$objrefs .= ' '.$esa['objid'].' 0 R';
				}
			}
			if (!empty($this->form_obj_id)) {
				foreach($this->form_obj_id as $objid) {
					$objrefs .= ' '.$objid.' 0 R';
				}
			}
			$out .= ' /Fields ['.$objrefs.']';
			// It's better to turn off this value and set the appearance stream for each annotation (/AP) to avoid conflicts with signature fields.
			if (empty($this->signature_data['approval']) OR ($this->signature_data['approval'] != 'A')) {
				$out .= ' /NeedAppearances false';
			}
			if ($this->sign AND isset($this->signature_data['cert_type'])) {
				if ($this->signature_data['cert_type'] > 0) {
					$out .= ' /SigFlags 3';
				} else {
					$out .= ' /SigFlags 1';
				}
			}
			//$out .= ' /CO ';
			if (isset($this->annotation_fonts) AND !empty($this->annotation_fonts)) {
				$out .= ' /DR <<';
				$out .= ' /Font <<';
				foreach ($this->annotation_fonts as $fontkey => $fontid) {
					$out .= ' /F'.$fontid.' '.$this->font_obj_ids[$fontkey].' 0 R';
				}
				$out .= ' >> >>';
			}
			$font = $this->getFontBuffer('helvetica');
			$out .= ' /DA (/F'.$font['i'].' 0 Tf 0 g)';
			$out .= ' /Q '.(($this->rtl)?'2':'0');
			//$out .= ' /XFA ';
			$out .= ' >>';
			// signatures
			if ($this->sign AND isset($this->signature_data['cert_type'])
				AND (empty($this->signature_data['approval']) OR ($this->signature_data['approval'] != 'A'))) {
				if ($this->signature_data['cert_type'] > 0) {
					$out .= ' /Perms << /DocMDP '.($this->sig_obj_id + 1).' 0 R >>';
				} else {
					$out .= ' /Perms << /UR3 '.($this->sig_obj_id + 1).' 0 R >>';
				}
			}
		}
		//$out .= ' /Legal <<>>';
		//$out .= ' /Requirements []';
		//$out .= ' /Collection <<>>';
		//$out .= ' /NeedsRendering true';
		$out .= ' >>';
		$out .= "\n".'endobj';
		$this->_out($out);
		return $oid;
	}

}
