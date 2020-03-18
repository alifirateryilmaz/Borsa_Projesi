<?php include "inc/header.php";
?>
<section class="section bg-color-quaternary custom-padding-4 border-0 my-0">
    <div class="container">
        <div class="row mb-3">
            <div class="col">

                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <!--table-responsive-lg-->
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <h3 class="custom-primary-font text-center custom-fontsize-3 font-weight-normal appear-animation"
                                data-appear-animation="fadeInUpShorter">En Çok Yükselenler</h3>
                            <thead>
                            <tr>
                                <th class="text-center" scope="col">Menkul Adı</th>
                                <th class="text-center" scope="col">Durum</th>
                                <th class="text-center" scope="col">Son Değeri</th>
                                <th class="text-center" scope="col">(%) Fark</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $link = "http://bigpara.hurriyet.com.tr/borsa/canli-borsa/";
                            $icerik = file_get_contents($link);
                            ///
                            $h_td_sembol = array();
                            $h_td_sembol = ara('target="_blank">', '</a>', $icerik);
                            $h_td_sembol = ara('target="_blank">', '</a>', $icerik);//hisse adlarının dizisi[0] - [99] arası 100 hisse
                            $tum_hisse_dizileri = array();
                            ///
                            for ($sayi = 0; $sayi < 100; $sayi++) {
                                $tum_hiss = array();
                                $hisse_tekil_yuzde = ara('h_td_yuzde_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                                $hisse_tekil_fiyat = ara('h_td_fiyat_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                                array_push($tum_hiss, $h_td_sembol[$sayi], convert_virgül_nokta($hisse_tekil_yuzde[0]), convert_virgül_nokta($hisse_tekil_fiyat[0]));
                                array_push($tum_hisse_dizileri, $tum_hiss);

                            }
                            $sorted = val_sort($tum_hisse_dizileri, 1);//1=yuzde

                            for ($sayi = 99; $sayi > 94; $sayi--) {
                                ?>
                                <tr>
                                    <td class="text-center"
                                        id="hisse_yukselen_sembol_<?php echo $sayi; ?>"><?php echo $sorted[$sayi][0]; ?></td>
                                    <td class="text-center"><i id="hisse_yukselen_durum_<?php echo $sayi; ?>"
                                                               class="<?php
                                                               if (convert_virgül_nokta($sorted[$sayi][1]) > 0) {
                                                                   echo "fas fa-arrow-circle-up text-success";
                                                               } elseif (convert_virgül_nokta($sorted[$sayi][1]) == 0) {
                                                                   echo "fas fa-minus text-info";
                                                               } else {
                                                                   echo "fas fa-arrow-circle-down text-danger";
                                                               }
                                                               ?>"></i></td>
                                    <td class="text-center"
                                        id="hisse_yukselen_son_deger_<?php echo $sayi; ?>"><?php echo $sorted[$sayi][2]; ?></td>
                                    <td class="text-center"
                                        id="hisse_yukselen_fark_<?php echo $sayi; ?>"><?php echo $sorted[$sayi][1]; ?></td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-lg-6">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <h3 class="custom-primary-font text-center custom-fontsize-3 font-weight-normal appear-animation"
                                data-appear-animation="fadeInUpShorter">En Çok Düşenler</h3>
                            <thead>
                            <tr>
                                <th class="text-center" scope="col">Menkul Adı</th>
                                <th class="text-center" scope="col">Durum</th>
                                <th class="text-center" scope="col">Son Değeri</th>
                                <th class="text-center" scope="col">(%) Fark</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            for ($sayi = 0; $sayi < 100; $sayi++) {
                                $tum_hiss = array();
                                $hisse_tekil_yuzde = ara('h_td_yuzde_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                                $hisse_tekil_fiyat = ara('h_td_fiyat_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                                array_push($tum_hiss, $h_td_sembol[$sayi], convert_virgül_nokta($hisse_tekil_yuzde[0]), convert_virgül_nokta($hisse_tekil_fiyat[0]));
                                array_push($tum_hisse_dizileri, $tum_hiss);//ayrı ayrı array oluşturup sıralama yapabilmek amacıyla
                            }
                            $sorted = val_sort($tum_hisse_dizileri, 1);//1=yuzde
                            for ($sayi = 0; $sayi < 5; $sayi++) {
                                ?>
                                <tr>
                                    <td class="text-center"
                                        id="hisse_dusen_sembol_<?php echo $sayi; ?>"><?php echo $sorted[$sayi][0]; ?></td>
                                    <td class="text-center"><i id="hisse_dusen_durum_<?php echo $sayi; ?>" class="<?php
                                        if (convert_virgül_nokta($sorted[$sayi][1]) > 0) {
                                            echo "fas fa-arrow-circle-up text-success";
                                        } elseif (convert_virgül_nokta($sorted[$sayi][1]) == 0) {
                                            echo "fas fa-minus text-info";
                                        } else {
                                            echo "fas fa-arrow-circle-down text-danger";
                                        }
                                        ?>"></i></td>
                                    <td class="text-center"
                                        id="hisse_dusen_son_deger_<?php echo $sayi; ?>"><?php echo $sorted[$sayi][2]; ?></td>
                                    <td class="text-center"
                                        id="hisse_dusen_fark_<?php echo $sayi; ?>"><?php echo $sorted[$sayi][1]; ?></td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="section bg-color-quaternary custom-padding-4 border-0 my-0">
    <div class="container">
        <h2 class="custom-primary-font text-center custom-fontsize-3 font-weight-normal appear-animation"
            data-appear-animation="fadeInUpShorter">BİST100 HİSSE VERİLERİ</h2>
        <table class="table table-responsive-lg table-bordered table-striped table-sm mb-0">
            <thead>
            <tr>
                <th scope="col">Menkul Adı</th>
                <th class="text-center" scope="col">Durum</th>
                <th class="text-center" scope="col">Son Değeri</th>
                <th class="text-center" scope="col">Fark</th>
                <th class="text-center" scope="col">(%) Fark</th>
                <th class="text-center" scope="col">En Düşük</th>
                <th class="text-center" scope="col">En Yüksek</th>
                <th class="text-center" scope="col">Hacim(Lot)</th>
                <th class="text-center" scope="col">Hacim(TL)</th>
                <th class="text-center" scope="col">Zaman</th>
                <?php if(isset($_SESSION['yetki'])){
                    echo "    <th class='text-center' scope='col'>Alış</th>
                              <th class='text-center' scope='col'>Satış</th>";
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <?php
            $komisyon=1.003;
            for ($sayi = 0; $sayi < 100; $sayi++) {
                ///
                $h_td_yuzde_id_deger = ara('h_td_yuzde_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                $h_td_fiyat_id_deger = ara('h_td_fiyat_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                $h_td_farktl_id_deger = ara('h_td_farktl_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                $h_td_dusuk_id_deger = ara('h_td_dusuk_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                $h_td_yuksek_id_deger = ara('h_td_yuksek_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                $h_td_hacimlot_id_deger = ara('h_td_hacimlot_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                $h_td_hacimtl_id_deger = ara('h_td_hacimtl_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                $h_td_saat_id_deger = ara('h_td_saat_id_' . $h_td_sembol[$sayi] . '">', '</li>', $icerik);
                ///
                ?>
                <tr>
                    <td class="text-center"
                        id="hisse_sembol_<?php echo $sayi; ?>"><?php echo $h_td_sembol[$sayi]; ?></td>
                    <td class="text-center"><i id="hisse_durum_<?php echo $sayi; ?>" class="
                        <?php
                        if (convert_virgül_nokta($h_td_yuzde_id_deger[0]) > 0) {
                            echo "fas fa-arrow-circle-up text-success";
                        } elseif (convert_virgül_nokta($h_td_yuzde_id_deger[0]) == 0) {
                            echo "fas fa-minus text-info";
                        } else {
                            echo "fas fa-arrow-circle-down text-danger";
                        }
                        ?>"></i></td>
                    <td class="text-center"
                        id="hisse_son_deger_<?php echo $sayi; ?>"><?php echo $h_td_fiyat_id_deger[0]; ?></td>
                    <td class="text-center"
                        id="hisse_fark_<?php echo $sayi; ?>"><?php echo $h_td_farktl_id_deger[0]; ?></td>
                    <td class="text-center"
                        id="hisse_fark_yuzde_<?php echo $sayi; ?>"><?php echo $h_td_yuzde_id_deger[0]; ?></td>
                    <td class="text-center"
                        id="hisse_en_dusuk_<?php echo $sayi; ?>"><?php echo $h_td_dusuk_id_deger[0]; ?></td>
                    <td class="text-center"
                        id="hisse_en_yuksek_<?php echo $sayi; ?>"><?php echo $h_td_yuksek_id_deger[0]; ?></td>
                    <td class="text-center"
                        id="hisse_hacim_lot_<?php echo $sayi; ?>"><?php echo $h_td_hacimlot_id_deger[0]; ?></td>
                    <td class="text-center"
                        id="hisse_hacim_tl_<?php echo $sayi; ?>"><?php echo $h_td_hacimtl_id_deger[0]; ?></td>
                    <td class="text-center"
                        id="hisse_zaman_<?php echo $sayi; ?>"><?php echo $h_td_saat_id_deger[0]; ?></td>
                    <?php if(isset($_SESSION['yetki'])){echo "<td class='text-center' id='hisse_alis_".$sayi." '>
                        <button id='btn_hisse_alis_" . $sayi . "' type='button' class='btn btn-success' data-toggle='modal' data-target='#formModal" . $sayi . "'>AL</button>
                        <div class='modal fade' id='formModal" . $sayi . "' tabindex='-1' role='dialog' aria-labelledby='formModalLabel' aria-hidden='true'>
                            <div class='modal-dialog'>
                                <div class='modal-content'>
                                    <div class='modal-header'>
                                        <h4 class='modal-title' id='formModalLabel'>Satın Al</h4>
                                        <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                                    </div>
                                    <div class='modal-body'>
                                        <form id='demo-form' class='mb-4' novalidate='novalidate'>
                                            <div class='form-group row align-items-center'>
                                                <label class='col-sm-4 text-left text-sm-right mb-0'>Hisse Adı: </label>
                                                <label class='col-sm-7 text-center  mb-0'>".$h_td_sembol[$sayi]."</label>
                                            </div>
                                            <div class='form-group row align-items-center'>
                                                <label class='col-sm-4 text-left text-sm-right mb-0'>Alış Tutarı: </label>
                                                <label id='hisse_deger_alim_" . $sayi . "' class='col-sm-7 text-center  mb-0'>".convert_virgül_nokta($h_td_fiyat_id_deger[0])."</label>
                                            </div>
                                            <div class='form-group row align-items-center'>
                                                <label class='col-sm-4 text-left text-sm-right mb-0'>Bakiyeniz: </label>
                                                <label class='col-sm-4 text-right  mb-0'>".$_SESSION['bakiye']."</label><span class='col-sm-4 text-left  mb-0'>&#x20BA;</span>
                                            </div>
                                            <div class='form-group row align-items-center'>
                                                <label class='col-sm-4 text-left text-sm-right mb-0'>Alınmak İstenen Miktar: </label>
                                                <div class='col-sm-7 text-center'>
                                                    <input id='range_" . $sayi . "' type = 'range' min='1' max='".intval($_SESSION['bakiye'])/(floatval(convert_virgül_nokta($h_td_fiyat_id_deger[0]))*$komisyon)."' onchange='alis_hesapla" . $sayi . "()'/>
                                                    <output  id='rangevalue" . $sayi . "'>50</output>
                                                </div>
                                            </div>
                                            <div class='form-group row align-items-center'>
                                                <label class='col-sm-4 text-left text-sm-right mb-0'>Komisyon(Binde 3): </label>
                                                <label id='komisyon" . $sayi . "' class='col-sm-4 text-right  mb-0'>".round(floatval(convert_virgül_nokta($h_td_fiyat_id_deger[0]))*($komisyon-1)*50,2)."</label><span class='col-sm-4 text-left  mb-0'>&#x20BA;</span>
                                            </div>
                                            <div class='form-group row align-items-center'>
                                                <label class='col-sm-4 text-left text-sm-right mb-0'>Toplam Ödenecek Tutar: </label>
                                                <label id='toplam_odenecek_alim_tutar" . $sayi . "' class='col-sm-4 text-right  mb-0'>
                                                <script>
                                                function alis_hesapla" . $sayi . "() {
                                                    var alis=$('#hisse_deger_alim_" . $sayi . "').text();
                                                    var miktar=$('#range_" . $sayi . "').val();
                                                    $('#rangevalue" . $sayi . "').text($('#range_" . $sayi . "').val());
                                                    $('#komisyon" . $sayi . "').text((alis*miktar*(".$komisyon."-1)).toFixed(2));
                                                    $('#toplam_odenecek_alim_tutar" . $sayi . "').text((alis*miktar*(".$komisyon. ")).toFixed(2));
                                              }
                                                </script>
                                                ".round(floatval(convert_virgül_nokta($h_td_fiyat_id_deger[0]))*($komisyon)*50,2)."
                                               </label><span class='col-sm-4 text-left  mb-0'>&#x20BA;</span>
                                            </div>
                                        </form>
                                    </div>
                                    <div class='modal-footer'>
                                        <button type='button' class='btn btn-light' data-dismiss='modal'>Kapat</button>
                                        <button type='button' class='btn btn-success'>Satın Al</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </td>
                    <td class='text-center' id='hisse_satis_".$sayi."'>
                     <button id='btn_hisse_satis_" . $sayi . "' type='button' class='btn btn-danger'>SAT</button>   
                                            </td>

                 </tr>
                        ";}
                        else{} ?>
            <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php include "inc/footer.php"; ?>

