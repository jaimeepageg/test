<?php

namespace Ceremonies\Services;

use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;

class PdfGenerator {

	private $mpdf;
	private $html;

	public function __construct() {
		$this->mpdf = new Mpdf();
		$this->mpdf->SetMargins(10, 10, 10);
		$this->mpdf->AddPage();
		$this->mpdf->WriteHTML($this->getCSS(), HTMLParserMode::HEADER_CSS);
	}

	public static function create() {
		return new self();
	}

	private function getCSS() {
		return file_get_contents(CER_RESOURCES_ROOT . 'pdf-templates/pdf-styles.css');
	}

	public function addContent($content) {
		$this->html .= $content;
		return $this;
	}

	public function setHeader($content) {
		$this->mpdf->SetHTMLHeader('
		');
		return $this;
	}

	public function setFooter($content) {
		$this->mpdf->SetHTMLFooter('
			<table width="100%" class="cer-footer">
			    <tr>
			        <td width="33%">{DATE j-m-Y}</td>
			        <td width="33%" align="center">{PAGENO}/{nbpg}</td>
			        <td width="33%" style="text-align: right;">'.$content.'</td>
			    </tr>
			</table>
		');
		return $this;
	}

	public function loadAndFill($template, $data) {
		ob_start();
		include CER_RESOURCES_ROOT . 'pdf-templates/' . $template . '.php';
		$this->addContent(ob_get_clean());
		return $this;
	}

	public function prepare() {
		$this->mpdf->WriteHTML($this->html);
		return $this;
	}

	public function export($fileName) {
		$this->mpdf->Output($fileName ?? 'exported-file.pdf', 'I');
	}

}